var SCANNER_VERSION = 'v16-perf';
window.SCANNER_VERSION = SCANNER_VERSION;
console.log('[Barcode Scanner] Running version', SCANNER_VERSION);

// Decode cadence. The old loop ran up to 5 full ZXing passes every 50ms with
// TRY_HARDER enabled, which pinned the CPU on phones. We now decode at most two
// candidates per tick, on a self-scheduling timeout, and only when the video
// actually produced a new frame.
var DECODE_TICK_MS = 120;
var RESCUE_EVERY = 5;
var ROI_MAX_W = 900;
var FULL_MAX_W = 960;

var _zxingPromise = null;

function loadZxing() {
    if (!_zxingPromise) {
        _zxingPromise = import('html5-qrcode/third_party/zxing-js.umd').catch(function (e) {
            _zxingPromise = null;
            throw e;
        });
    }
    return _zxingPromise;
}

function zxingNamespace(mod) {
    if (mod && mod.default && mod.default.MultiFormatReader) return mod.default;
    if (mod && mod.z && mod.z.MultiFormatReader) return mod.z;
    if (mod && mod.MultiFormatReader) return mod;
    for (var k in mod) {
        var v = mod[k];
        if (v && v.MultiFormatReader) return v;
    }
    return mod;
}

window.PriceCheckerScanner = {
    instances: {},
    start: function (containerId) {
        return window.scanBarcode({ containerId: containerId || null });
    },
    stop: function () {
        if (window.stopScanBarcode) window.stopScanBarcode();
    }
};

window._scanAbort = null;
window._scannerInstance = null;
window._activeScan = null;

function toDecodedText(value) {
    if (value == null) return '';
    if (typeof value === 'string') return value.trim();
    if (typeof value === 'object') {
        var t = value.decodedText || value.text || value.data || value.resultText || '';
        if (typeof t === 'string' && t.length) return t.trim();
        if (value.result && typeof value.result.text === 'string' && value.result.text.length) {
            return value.result.text.trim();
        }
    }
    return '';
}

window.normalizeScanText = toDecodedText;

function errorNameFrom(err) {
    if (!err) return '';
    if (typeof err === 'object' && err.name) return String(err.name);
    var m = String(err).match(/([A-Za-z]+Error)[:]?/);
    return m ? m[1] : String(err);
}

function getCameraList() {
    if (!navigator.mediaDevices || !navigator.mediaDevices.enumerateDevices) return Promise.resolve([]);
    return navigator.mediaDevices.enumerateDevices().then(function (devices) {
        var list = [];
        for (var i = 0; i < devices.length; i++) {
            if (devices[i].kind === 'videoinput') {
                list.push({ id: devices[i].deviceId, label: devices[i].label || '' });
            }
        }
        return list;
    }).catch(function () { return []; });
}

/**
 * Reusable ZXing decoder. The reader is built once per scan (building a
 * MultiFormatReader allocates its format lookup tables, which is far too
 * expensive to repeat for every candidate of every tick).
 */
function createDecoder(ZXing) {
    var formats = [
        ZXing.BarcodeFormat.EAN_13,
        ZXing.BarcodeFormat.EAN_8,
        ZXing.BarcodeFormat.UPC_A,
        ZXing.BarcodeFormat.UPC_E,
        ZXing.BarcodeFormat.CODE_128,
        ZXing.BarcodeFormat.CODE_39,
        ZXing.BarcodeFormat.CODE_93,
        ZXing.BarcodeFormat.ITF,
        ZXing.BarcodeFormat.RSS_14,
        ZXing.BarcodeFormat.QR_CODE
    ];
    var baseHints = new Map();
    baseHints.set(ZXing.DecodeHintType.POSSIBLE_FORMATS, formats);

    var hardHints = new Map(baseHints);
    hardHints.set(ZXing.DecodeHintType.TRY_HARDER, true);

    var readers = { fast: new ZXing.MultiFormatReader(false, baseHints), hard: new ZXing.MultiFormatReader(false, hardHints) };

    return {
        /**
         * @param {HTMLCanvasElement} canvas
         * @param {boolean} hard  use the TRY_HARDER reader (rescue passes only)
         */
        decode: function (canvas, hard) {
            var reader = hard ? readers.hard : readers.fast;
            var source = new ZXing.HTMLCanvasElementLuminanceSource(canvas);
            var bitmap = new ZXing.BinaryBitmap(new ZXing.HybridBinarizer(source));
            var result = reader.decode(bitmap);
            return result ? (result.text || '') : '';
        }
    };
}

var NATIVE_FORMATS = ['ean_13', 'ean_8', 'upc_a', 'upc_e', 'code_128', 'code_39', 'code_93', 'itf', 'qr_code', 'databar'];

/**
 * Prefer the browser's native BarcodeDetector when it exists (Chrome on Android,
 * Safari 17.4+). It decodes off the main thread's critical path and needs no
 * canvas copy at all, which is the single biggest win on modern phones.
 * @returns {Promise<BarcodeDetector|null>}
 */
function createNativeDetector() {
    try {
        if (typeof window === 'undefined' || !window.BarcodeDetector) return Promise.resolve(null);
        var supported = window.BarcodeDetector.getSupportedFormats
            ? window.BarcodeDetector.getSupportedFormats()
            : Promise.resolve(NATIVE_FORMATS);
        return supported.then(function (list) {
            if (!list) return null;
            var formats = NATIVE_FORMATS.filter(function (f) { return list.indexOf(f) !== -1; });
            if (!formats.length) return null;
            return new window.BarcodeDetector({ formats: formats });
        }).catch(function () { return null; });
    } catch (e) {
        return Promise.resolve(null);
    }
}

function buildCameraConstraints(deviceId, kind) {
    // 640x480 is plenty for 1D/2D barcode decoding and is ~2.8x less pixel
    // work per frame than the previous 1280x720 request.
    var size = { width: { ideal: 640 }, height: { ideal: 480 } };
    var advanced = [
        { focusMode: 'continuous' },
        { exposureMode: 'continuous' },
        { whiteBalanceMode: 'continuous' }
    ];
    if (deviceId) {
        return { audio: false, video: { deviceId: { exact: deviceId }, width: size.width, height: size.height, frameRate: { ideal: 30, max: 30 }, advanced: advanced } };
    }
    var v = { width: size.width, height: size.height, frameRate: { ideal: 30, max: 30 }, advanced: advanced };
    if (kind === 'env') {
        v.facingMode = { ideal: 'environment' };
    } else if (kind === 'user') {
        v.facingMode = { ideal: 'user' };
    }
    return { audio: false, video: v };
}

function ensureScannerStyles() {
    if (document.getElementById('md-scanner-style')) return;
    var style = document.createElement('style');
    style.id = 'md-scanner-style';
    style.textContent =
        '@keyframes mdScanSweep{0%{top:0}50%{top:calc(100% - 3px)}100%{top:0}}' +
        '@keyframes mdFramePulse{0%,100%{border-color:rgba(16,185,129,.55);box-shadow:0 0 0 rgba(16,185,129,.15),0 0 18px rgba(16,185,129,.25) inset}50%{border-color:rgba(244,63,94,.65);box-shadow:0 0 0 rgba(244,63,94,.2),0 0 26px rgba(244,63,94,.35) inset}}' +
        '.md-viewport{position:relative;width:100%;overflow:hidden;background:#000;border-radius:12px;}' +
        '.md-viewport video{position:absolute;inset:0;width:100%;height:100%;object-fit:cover;}' +
        '.md-scanline{position:absolute;left:6%;right:6%;top:0;height:3px;z-index:5;pointer-events:none;background:linear-gradient(90deg,rgba(16,185,129,0),#10b981 18%,#f43f5e 50%,#10b981 82%,rgba(16,185,129,0));box-shadow:0 0 14px 2px rgba(16,185,129,.8),0 0 4px rgba(16,185,129,.9);animation:mdScanSweep 1.4s ease-in-out infinite;}' +
        '.md-scanframe{position:absolute;left:4%;right:4%;top:7%;bottom:7%;border:2px solid rgba(16,185,129,.55);border-radius:10px;z-index:5;pointer-events:none;animation:mdFramePulse 1.4s ease-in-out infinite;}' +
        '.md-scanframe::before,.md-scanframe::after{content:"";position:absolute;width:22px;height:22px;border:3px solid #10b981;}' +
        '.md-scanframe::before{left:-2px;top:-2px;border-right:none;border-bottom:none;border-radius:8px 0 0 0;}' +
        '.md-scanframe::after{right:-2px;bottom:-2px;border-left:none;border-top:none;border-radius:0 0 8px 0;}' +
        '.md-switch-cam{position:absolute;top:8px;right:8px;z-index:6;background:rgba(15,23,42,.85);color:#fff;border:1px solid rgba(255,255,255,.3);border-radius:9999px;padding:4px 10px;font-size:11px;line-height:1.4;cursor:pointer;pointer-events:auto;}' +
        '.md-torch{position:absolute;top:8px;left:8px;z-index:6;background:rgba(15,23,42,.85);color:#fff;border:1px solid rgba(255,255,255,.3);border-radius:9999px;padding:4px 10px;font-size:11px;line-height:1.4;cursor:pointer;pointer-events:auto;}' +
        '.md-torch.md-on{background:#f59e0b;border-color:#fbbf24;color:#1f2937;font-weight:600;}' +
        '.md-scanhint{position:absolute;left:8%;right:8%;bottom:8px;z-index:6;text-align:center;font-size:12px;line-height:1.4;color:#fef3c7;background:rgba(15,23,42,.75);border:1px solid rgba(251,191,36,.4);border-radius:8px;padding:6px 10px;pointer-events:none;}' +
        '.md-fallback{position:absolute;inset:0;z-index:7;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:12px;padding:16px;text-align:center;background:#0f172a;color:#fff;}' +
        '.md-fallback p{font-size:13px;line-height:1.5;color:#cbd5e1;max-width:26rem;margin:0;}' +
        '.md-fallback-btns{display:flex;gap:8px;flex-wrap:wrap;justify-content:center;}' +
        '.md-fallback button{pointer-events:auto;padding:8px 14px;border-radius:10px;font-size:13px;font-weight:600;cursor:pointer;border:1px solid rgba(255,255,255,.25);background:rgba(255,255,255,.1);color:#fff;}' +
        '.md-fallback button.md-primary{background:#10b981;border-color:#10b981;color:#04291c;}';
    document.head.appendChild(style);
}

function primeScanAudio() {
    if (window._scanAudioCtx) {
        try {
            if (window._scanAudioCtx.state === 'suspended') window._scanAudioCtx.resume();
        } catch (e) {}
        return;
    }
    try {
        var Ctx = window.AudioContext || window.webkitAudioContext;
        if (Ctx) window._scanAudioCtx = new Ctx();
    } catch (e) {}
}

function playScanFeedback() {
    if (navigator.vibrate) {
        try { navigator.vibrate(60); } catch (e) {}
    }
    var ctx = window._scanAudioCtx;
    try {
        if (!ctx) {
            var Ctx = window.AudioContext || window.webkitAudioContext;
            if (!Ctx) return;
            ctx = window._scanAudioCtx = new Ctx();
        }
        if (ctx.state === 'suspended') { try { ctx.resume(); } catch (e) {} }
        if (ctx.state !== 'running') return;
        var osc = ctx.createOscillator();
        var gain = ctx.createGain();
        osc.type = 'sine';
        osc.frequency.value = 1568;
        gain.gain.setValueAtTime(0.15, ctx.currentTime);
        gain.gain.exponentialRampToValueAtTime(0.0001, ctx.currentTime + 0.12);
        osc.connect(gain);
        gain.connect(ctx.destination);
        osc.start();
        osc.stop(ctx.currentTime + 0.12);
        osc.onended = function () {
            try { osc.disconnect(); gain.disconnect(); } catch (e) {}
        };
    } catch (e) {}
}

function showScanMessage(message) {
    if (!message) return;
    ensureScanMessageStyle();
    var existing = document.getElementById('qr-scan-toast');
    if (existing) existing.remove();
    var toast = document.createElement('div');
    toast.className = 'qr-scan-toast';
    toast.id = 'qr-scan-toast';
    toast.textContent = message;
    document.body.appendChild(toast);
    setTimeout(function () {
        var t = document.getElementById('qr-scan-toast');
        if (t) t.remove();
    }, 4500);
}

function ensureScanMessageStyle() {
    if (document.getElementById('qr-scan-toast-style')) return;
    var style = document.createElement('style');
    style.id = 'qr-scan-toast-style';
    style.textContent =
        '.qr-scan-toast{position:fixed;top:16px;left:50%;transform:translate(-50%,0);z-index:99999;background:rgba(15,23,42,0.95);color:#fff;padding:10px 16px;border-radius:10px;font-size:14px;line-height:1.5;max-width:90%;box-shadow:0 4px 16px rgba(0,0,0,0.3);pointer-events:none;animation:qrToastIn .2s ease-out;}@keyframes qrToastIn{from{opacity:0;transform:translate(-50%,-8px)}to{opacity:1;transform:translate(-50%,0)}}';
    document.head.appendChild(style);
}

function imageToCanvas(file, maxDim) {
    return new Promise(function (resolve, reject) {
        var url = URL.createObjectURL(file);
        var img = new Image();
        img.onload = function () {
            var cap = maxDim || 4096;
            var scale = Math.min(1, cap / Math.max(img.width, img.height));
            var c = document.createElement('canvas');
            c.width = Math.max(1, Math.round(img.width * scale));
            c.height = Math.max(1, Math.round(img.height * scale));
            var ctx = c.getContext('2d');
            if (!ctx) { URL.revokeObjectURL(url); reject('Canvas not supported'); return; }
            ctx.drawImage(img, 0, 0, c.width, c.height);
            URL.revokeObjectURL(url);
            resolve(c);
        };
        img.onerror = function () { URL.revokeObjectURL(url); reject('Could not read image'); };
        img.src = url;
    });
}

function stopDecodeLoop(scan) {
    if (scan.decodeTimer) {
        clearTimeout(scan.decodeTimer);
        scan.decodeTimer = null;
    }
}

function stopScan(scan, reason) {
    if (scan.cancelled) return;
    scan.cancelled = true;
    stopDecodeLoop(scan);
    scan.removeVisibilityHook && scan.removeVisibilityHook();
    if (scan.currentStream) {
        try {
            scan.currentStream.getTracks().forEach(function (t) { try { t.stop(); } catch (e) {} });
        } catch (e) {}
        scan.currentStream = null;
    }
    if (scan.videoEl && scan.videoEl.parentNode) {
        try { scan.videoEl.parentNode.removeChild(scan.videoEl); } catch (e) {}
    }
    scan.videoEl = null;
    if (scan.container) {
        try { scan.container.innerHTML = ''; } catch (e) {}
    }
    if (window._scanAbort) {
        try { window._scanAbort.abort(); } catch (e) {}
        window._scanAbort = null;
    }
    if (window._activeScan === scan) window._activeScan = null;
    if (window._scannerInstance && window._scannerInstance._scan === scan) window._scannerInstance = null;
    if (reason === 'Cancelled') {
        try { scan.reject('Cancelled'); } catch (e) {}
    }
}

function finishScan(scan, text) {
    stopScan(scan);
    try { scan.resolve(text); } catch (e) {}
}

function addScanOverlays(scan) {
    var el = scan.container;
    if (!el) return;
    ensureScannerStyles();
    if (!el.querySelector('.md-scanline')) {
        var line = document.createElement('div');
        line.className = 'md-scanline';
        el.appendChild(line);
        var frame = document.createElement('div');
        frame.className = 'md-scanframe';
        el.appendChild(frame);
    }
    if (scan.cameraList.length >= 2 && !el.querySelector('.md-switch-cam')) {
        var btn = document.createElement('button');
        btn.className = 'md-switch-cam';
        btn.type = 'button';
        btn.textContent = 'Switch camera';
        btn.addEventListener('click', function () { switchCamera(scan); });
        el.appendChild(btn);
    }
}

function enrichCameraList(scan) {
    getCameraList().then(function (list) {
        scan.cameraList = list;
        if (scan.cancelled) return;
        var el = scan.container;
        if (el && list.length >= 2 && !el.querySelector('.md-switch-cam')) {
            var btn = document.createElement('button');
            btn.className = 'md-switch-cam';
            btn.type = 'button';
            btn.textContent = 'Switch camera';
            btn.addEventListener('click', function () { switchCamera(scan); });
            el.appendChild(btn);
        }
    }).catch(function () {});
}

function canUseTorch(scan) {
    if (!scan.currentStream) return false;
    var track = scan.currentStream.getVideoTracks && scan.currentStream.getVideoTracks()[0];
    if (!track) return false;
    if (track.getCapabilities && track.getCapabilities().torch) return true;
    return typeof track.applyConstraints === 'function';
}

function addTorchButton(scan) {
    var el = scan.container;
    if (!el || !canUseTorch(scan)) return;
    if (el.querySelector('.md-torch')) return;
    var btn = document.createElement('button');
    btn.className = 'md-torch';
    btn.type = 'button';
    btn.textContent = 'Flashlight';
    btn.addEventListener('click', function () {
        setTorch(scan, !scan.torchOn).then(function (ok) {
            if (!ok || scan.cancelled) return;
            scan.torchOn = !scan.torchOn;
            btn.classList.toggle('md-on', scan.torchOn);
            btn.textContent = scan.torchOn ? 'Flashlight ON' : 'Flashlight';
        });
    });
    el.appendChild(btn);
}

function setTorch(scan, on) {
    if (!scan.currentStream) return Promise.resolve(false);
    var track = scan.currentStream.getVideoTracks && scan.currentStream.getVideoTracks()[0];
    if (!track || typeof track.applyConstraints !== 'function') return Promise.resolve(false);
    try {
        return track.applyConstraints({ advanced: [{ torch: !!on }] }).then(function () {
            return true;
        }).catch(function () {
            return false;
        });
    } catch (e) {
        return Promise.resolve(false);
    }
}

function switchCamera(scan) {
    if (scan.cancelled || scan.cameraList.length < 2) return;
    scan.cameraIndex = (scan.cameraIndex + 1) % scan.cameraList.length;
    var id = scan.cameraList[scan.cameraIndex] ? scan.cameraList[scan.cameraIndex].id : null;
    stopDecodeLoop(scan);
    if (scan.currentStream) {
        try { scan.currentStream.getTracks().forEach(function (t) { try { t.stop(); } catch (e) {} }); } catch (e) {}
        scan.currentStream = null;
    }
    scan.videoEl = null;
    if (scan.container) scan.container.innerHTML = '';
    startLiveScanner(scan, 0, id);
}

function showFallback(scan, message, opts) {
    if (scan.cancelled) return;
    stopDecodeLoop(scan);
    if (scan.currentStream) {
        try { scan.currentStream.getTracks().forEach(function (t) { try { t.stop(); } catch (e) {} }); } catch (e) {}
        scan.currentStream = null;
    }
    scan.videoEl = null;
    var el = scan.container;
    if (!el) return;
    el.innerHTML = '';
    var div = document.createElement('div');
    div.className = 'md-fallback';
    var p = document.createElement('p');
    p.textContent = message || 'Camera is unavailable.';
    div.appendChild(p);
    var btns = document.createElement('div');
    btns.className = 'md-fallback-btns';
    if (opts && opts.retry) {
        var retryBtn = document.createElement('button');
        retryBtn.textContent = 'Try Again';
        retryBtn.addEventListener('click', function () {
            if (!scan.cancelled) startLiveScanner(scan, 0, null);
        });
        btns.appendChild(retryBtn);
    }
    if (opts && opts.photo) {
        var photoBtn = document.createElement('button');
        photoBtn.className = 'md-primary';
        photoBtn.textContent = 'Choose Photo';
        photoBtn.addEventListener('click', function () {
            if (!scan.cancelled) startFileScan(scan);
        });
        btns.appendChild(photoBtn);
    }
    if (opts && opts.https && window.location.protocol === 'http:') {
        var httpsBtn = document.createElement('button');
        httpsBtn.textContent = 'Open with HTTPS';
        httpsBtn.addEventListener('click', function () {
            window.location.href = 'https://' + window.location.host + window.location.pathname + window.location.search;
        });
        btns.appendChild(httpsBtn);
    }
    div.appendChild(btns);
    el.appendChild(div);
}

function failAttempt(scan, phase, attempt, deviceId, err) {
    if (scan.cancelled) return;
    stopDecodeLoop(scan);
    if (scan.currentStream) {
        try { scan.currentStream.getTracks().forEach(function (t) { try { t.stop(); } catch (e) {} }); } catch (e) {}
        scan.currentStream = null;
    }
    scan.videoEl = null;
    if (scan.container) scan.container.innerHTML = '';
    var eName = errorNameFrom(err);
    var lower = (eName || '').toLowerCase();
    var msg = (err && err.message) || String(err || '');
    var lowerMsg = msg.toLowerCase();
    var detail = eName ? ' [' + eName + ']' : '';
    console.error('[scanBarcode] Camera error (' + phase + '):', err, 'attempt', attempt);

    var isNotAllowed = /notallowed|permission|denied/.test(lower) || /permission|denied|policy/.test(lowerMsg);
    var isNotFound = /notfound/.test(lower) || /not found/.test(lowerMsg);
    var isBusy = /notreadable|abort|inuse|startfailed/.test(lower) || /in use|busy|another application/.test(lowerMsg);
    var isOver = /overconstrained/.test(lower);
    var isSecurity = /securityerror/.test(lower) || /secure context/.test(lowerMsg);

    if (isNotFound) {
        showFallback(scan, 'No camera was found on this device. You can still scan a barcode from a photo.', { photo: true });
        return;
    }
    if (isSecurity || !window.isSecureContext) {
        showFallback(scan, 'Camera is blocked because this site is not being served over a secure HTTPS connection on this device.' + detail + ' Open the site using https:// (and allow camera access), then scan again.', { photo: true, retry: true, https: true });
        return;
    }
    if (isNotAllowed) {
        var rawDetail = '';
        if (msg && msg !== eName) rawDetail = ' (' + msg + ')';
        var policyBlocked = /policy|permission denied by|denied by system|not allowed by the user agent/i.test(lowerMsg);
        var hint;
        if (policyBlocked) {
            hint = 'This is caused by a security header on the server (Permissions-Policy) still blocking the camera. The server must send "camera=(self)". Until then you can scan from a photo.';
        } else {
            hint = 'Tap the camera or lock icon in the address bar, set Camera to "Allow", then refresh and scan again. On Windows also check Settings > Privacy > Camera.';
        }
        showFallback(scan, 'Camera permission was blocked by the browser.' + detail + rawDetail + ' ' + hint, { photo: true, retry: true, https: true });
        return;
    }
    if (isOver) {
        if (deviceId) { startLiveScanner(scan, 1, null); return; }
        if (attempt < 3) { startLiveScanner(scan, attempt + 1, null); return; }
        showFallback(scan, 'Could not find a suitable camera. You can still scan a barcode from a photo.', { photo: true });
        return;
    }
    if (isBusy) {
        if (attempt < 3) {
            showScanMessage('The camera is busy. Retrying…');
            setTimeout(function () { if (!scan.cancelled) startLiveScanner(scan, attempt + 1, deviceId); }, 900);
            return;
        }
        showFallback(scan, 'The camera is being used by another app or tab. Close it, then tap Try Again.', { photo: true, retry: true });
        return;
    }
    if (deviceId) { startLiveScanner(scan, 1, null); return; }
    if (attempt < 2) { startLiveScanner(scan, attempt + 1, null); return; }
    showFallback(scan, 'The camera could not be started (' + (eName || 'unknown error') + '). Tap Try Again or scan a barcode from a photo.', { photo: true, retry: true });
}

function startDecodeLoop(scan, ZXing) {
    if (scan.cancelled) return;
    var decoder = createDecoder(ZXing);
    var canvases = {
        roi: document.createElement('canvas'),
        roiLow: document.createElement('canvas'),
        full: document.createElement('canvas'),
        bright: document.createElement('canvas'),
        invert: document.createElement('canvas')
    };
    var tick = 0;
    var lastVideoTime = -1;
    var nativeBroken = false;
    var nativeBusy = false;
    var reportedError = false;
    var startedAt = Date.now();
    var hintShown = false;
    var hintEl = null;

    // Extra candidates rotate across ticks instead of all running every frame.
    var rotation = ['roiLow', 'full', 'none', 'roiLow'];

    function showHint() {
        if (hintShown || !scan.container) return;
        hintShown = true;
        hintEl = document.createElement('div');
        hintEl.className = 'md-scanhint';
        hintEl.textContent = 'No barcode found — hold the code closer and keep it steady';
        scan.container.appendChild(hintEl);
    }

    function schedule() {
        stopDecodeLoop(scan);
        if (scan.cancelled) return;
        // A self-scheduling timeout means decode work can never queue up behind
        // itself the way a fixed setInterval did.
        scan.decodeTimer = window.setTimeout(step, DECODE_TICK_MS);
    }

    function step() {
        if (scan.cancelled) return;
        if (scan.decodeTimer) scan.decodeTimer = null;

        // Never burn battery in a background tab.
        if (typeof document !== 'undefined' && document.hidden) {
            schedule();
            return;
        }

        var video = scan.videoEl;
        var vw = video ? video.videoWidth : 0;
        var vh = video ? video.videoHeight : 0;
        if (!vw || !vh || !scan.currentStream) {
            schedule();
            return;
        }

        // Only decode when the camera actually delivered a new frame.
        if (video.currentTime === lastVideoTime) {
            schedule();
            return;
        }
        lastVideoTime = video.currentTime;

        try {
            if (!nativeBroken && scan.nativeDetector) {
                // detect() is async, so a plain schedule() would let calls pile
                // up on a slow device. Only ever keep one in flight.
                if (nativeBusy) { schedule(); return; }
                nativeBusy = true;
                scan.nativeDetector.detect(video).then(function (codes) {
                    if (scan.cancelled) return;
                    var first = codes && codes.length ? toDecodedText(codes[0]) : '';
                    if (first) {
                        playScanFeedback();
                        finishScan(scan, first);
                    }
                }).catch(function (e) {
                    // One failure is enough: fall back to ZXing for the session.
                    if (!scan.cancelled && !nativeBroken) {
                        nativeBroken = true;
                        scan.nativeDetector = null;
                        console.warn('[scanBarcode] native BarcodeDetector failed, using ZXing:', e);
                    }
                }).then(function () {
                    nativeBusy = false;
                });
                schedule();
                return;
            }

            var extra = rotation[tick % rotation.length];
            var text = decodeCandidates(decoder, canvases, video, vw, vh, extra, tick % RESCUE_EVERY === 0);
            tick++;
            if (text) {
                playScanFeedback();
                finishScan(scan, toDecodedText(text));
                return;
            }
        } catch (e) {
            if (!reportedError) {
                reportedError = true;
                console.warn('[scanBarcode] frame error:', e);
            }
        }

        if (Date.now() - startedAt > 2000) showHint();

        schedule();
    }

    createNativeDetector().then(function (detector) {
        if (scan.cancelled) return;
        scan.nativeDetector = detector;
        schedule();
    });
}

function drawToCanvas(canvas, video, sx, sy, sw, sh, dw, dh) {
    if (canvas.width !== dw || canvas.height !== dh) {
        canvas.width = dw;
        canvas.height = dh;
    }
    var ctx = canvas.getContext('2d', { willReadFrequently: true });
    if (!ctx) return null;
    // Rescue passes set a canvas filter; reset it so the next pass is not
    // permanently brightened or inverted.
    try { ctx.filter = 'none'; } catch (e) {}
    ctx.clearRect(0, 0, dw, dh);
    ctx.drawImage(video, sx, sy, sw, sh, 0, 0, dw, dh);
    return ctx;
}

function decodeCanvasSafely(decoder, canvas, hard) {
    if (!canvas || !decoder) return '';
    try {
        return toDecodedText(decoder.decode(canvas, hard));
    } catch (e) {
        return '';
    }
}

/**
 * Decode one tick's candidates, cheapest/most likely first.
 *  1. Region of interest (the visible scan frame) at native scale - every tick
 *  2. One rotating second candidate: downscaled ROI, or the full frame
 *  3. Brightened + inverted full frames (rescue) only every RESCUE_EVERY ticks
 *
 * The previous implementation attempted all five of these every 50ms, which is
 * what made phones stutter and heat up. Now a normal tick costs 1-2 decodes and
 * a rescue tick at most 4, and ticks are 120ms apart and self-scheduling.
 */
function decodeCandidates(decoder, canvases, video, vw, vh, extra, allowRescue) {
    var text = '';

    var rx = Math.round(vw * 0.06);
    var ry = Math.round(vh * 0.08);
    var rw = Math.round(vw * 0.88);
    var rh = Math.round(vh * 0.84);

    var roiW = Math.min(rw, ROI_MAX_W);
    var roiH = Math.max(1, Math.round(rh * (roiW / rw)));
    drawToCanvas(canvases.roi, video, rx, ry, rw, rh, roiW, roiH);
    text = decodeCanvasSafely(decoder, canvases.roi, false);
    if (text) return text;

    var fullW = Math.min(vw, FULL_MAX_W);
    var fullH = Math.max(1, Math.round(vh * (fullW / vw)));

    if (extra === 'roiLow' && roiW > 560) {
        var lowW = Math.max(400, Math.round(roiW * 0.6));
        var lowH = Math.max(1, Math.round(roiH * (lowW / roiW)));
        drawToCanvas(canvases.roiLow, video, rx, ry, rw, rh, lowW, lowH);
        text = decodeCanvasSafely(decoder, canvases.roiLow, false);
        if (text) return text;
    }

    if (extra === 'full') {
        drawToCanvas(canvases.full, video, 0, 0, vw, vh, fullW, fullH);
        text = decodeCanvasSafely(decoder, canvases.full, false);
        if (text) return text;
    }

    if (allowRescue) {
        var brightCtx = drawToCanvas(canvases.bright, video, 0, 0, vw, vh, fullW, fullH);
        if (brightCtx) {
            try { brightCtx.filter = 'brightness(1.35) contrast(1.25) saturate(1.1)'; } catch (e) {}
            brightCtx.drawImage(video, 0, 0, fullW, fullH);
            text = decodeCanvasSafely(decoder, canvases.bright, true);
            if (text) return text;
        }

        var invertCtx = drawToCanvas(canvases.invert, video, 0, 0, vw, vh, fullW, fullH);
        if (invertCtx) {
            try { invertCtx.filter = 'invert(1)'; } catch (e) {}
            invertCtx.drawImage(video, 0, 0, fullW, fullH);
            text = decodeCanvasSafely(decoder, canvases.invert, true);
            if (text) return text;
        }
    }

    return '';
}

function startLiveScanner(scan, attempt, deviceId) {
    if (scan.cancelled) return;
    var el = scan.container;
    if (!el) { try { scan.reject('Scanner area not found'); } catch (e) {} return; }
    el.innerHTML = '';
    if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
        showFallback(scan, 'This browser does not support in-page camera scanning. You can scan a barcode from a photo instead.', { photo: true });
        return;
    }
    var kind = null;
    if (!deviceId) {
        if (attempt === 0) kind = 'env';
        else if (attempt === 1) kind = null;
        else if (attempt === 2) kind = 'user';
    }
    var constraints = buildCameraConstraints(deviceId, kind);

    navigator.mediaDevices.getUserMedia(constraints).then(function (stream) {
        if (scan.cancelled) {
            try { stream.getTracks().forEach(function (t) { t.stop(); }); } catch (e) {}
            return;
        }
        scan.currentStream = stream;
        el.innerHTML = '';
        var video = document.createElement('video');
        video.setAttribute('playsinline', '');
        video.setAttribute('autoplay', '');
        video.setAttribute('muted', '');
        video.muted = true;
        video.srcObject = stream;
        el.appendChild(video);
        scan.videoEl = video;
        window._scannerInstance = { stop: function () { stopScan(scan, 'Cancelled'); }, _scan: scan };
        addScanOverlays(scan);
        enrichCameraList(scan);
        addTorchButton(scan);
        var playP = video.play();
        if (playP && playP.then) {
            playP.catch(function () {
                if (!scan.cancelled) {
                    setTimeout(function () {
                        if (scan.videoEl === video && video.parentNode) {
                            var retryP = video.play();
                            if (retryP && retryP.then) retryP.catch(function () {});
                        }
                    }, 250);
                }
            });
        }
        loadZxing().then(function (mod) {
            if (!scan.cancelled) startDecodeLoop(scan, zxingNamespace(mod));
        }).catch(function () {
            if (!scan.cancelled) {
                showFallback(scan, 'Scanner engine failed to load on this device.', { photo: true, retry: true });
            }
        });
    }).catch(function (err) {
        failAttempt(scan, 'getUserMedia', attempt, deviceId, err);
    });
}

function startFileScan(scan) {
    if (scan.cancelled) return;
    if (scan.container) scan.container.innerHTML = '';
    var ac = new AbortController();
    window._scanAbort = ac;
    var input = document.createElement('input');
    input.type = 'file';
    input.accept = 'image/*';
    input.onchange = function (e) {
        if (scan.cancelled || ac.signal.aborted) { input.remove(); return; }
        var file = e.target.files && e.target.files[0];
        if (!file) { input.remove(); return; }
        input.remove();
        loadZxing().then(function (mod) {
            var decoder = createDecoder(zxingNamespace(mod));
            decodeFileMultiScale(scan, file, decoder).then(function (text) {
                if (!scan.cancelled && !ac.signal.aborted) {
                    playScanFeedback();
                    finishScan(scan, toDecodedText(text));
                }
            }).catch(function () {
                if (scan.cancelled || ac.signal.aborted) return;
                showFallback(scan, 'Could not read a barcode from that image. Try a clearer, closer photo.', { photo: true });
            });
        }).catch(function () {
            if (!scan.cancelled && !ac.signal.aborted) {
                showFallback(scan, 'Scanner engine failed to load.', { photo: true });
            }
        });
    };
    input.onerror = function () {
        if (!scan.cancelled) showFallback(scan, 'Could not open the photo picker.', { photo: true, retry: true });
        input.remove();
    };
    document.body.appendChild(input);
    input.click();
}

function decodeFileMultiScale(scan, file, decoder) {
    var scales = [null, 1600, 1024, 768, 512];
    function attempt(i) {
        if (scan.cancelled) return Promise.reject('Cancelled');
        if (i >= scales.length) return Promise.reject('Could not read barcode from that image');
        return imageToCanvas(file, scales[i]).then(function (c) {
            try {
                // Photos are a one-shot: use the TRY_HARDER reader here.
                var text = decoder.decode(c, true);
                if (text) return toDecodedText(text);
            } catch (e) {}
            return attempt(i + 1);
        }).catch(function (e) {
            if (e === 'Cancelled') return Promise.reject('Cancelled');
            return attempt(i + 1);
        });
    }
    return attempt(0);
}

window.scanBarcode = function (options) {
    options = options || {};
    ensureScannerStyles();
    primeScanAudio();

    var scan = {
        cancelled: false,
        cameraList: [],
        cameraIndex: 0,
        currentStream: null,
        decodeTimer: null,
        videoEl: null,
        nativeDetector: null,
        removeVisibilityHook: null,
        containerId: options.containerId || null,
        container: (options.containerId ? document.getElementById(options.containerId) : null) || null
    };

    if (window._activeScan && window._activeScan !== scan) {
        try { stopScan(window._activeScan, 'Cancelled'); } catch (e) {}
    }
    window._activeScan = scan;

    // A closed modal or an abandoned page used to leave the camera stream and
    // the decode timer running, which kept the device hot for as long as the
    // tab stayed open. Release the hardware on hide and on unload.
    if (!options.keepAliveOnHide) {
        var onHidden = function () {
            if (!document.hidden) return;
            if (scan.currentStream) {
                try { scan.currentStream.getTracks().forEach(function (t) { try { t.stop(); } catch (e) {} }); } catch (e) {}
                scan.currentStream = null;
            }
            if (scan.videoEl && scan.videoEl.parentNode) {
                try { scan.videoEl.parentNode.removeChild(scan.videoEl); } catch (e) {}
            }
            scan.videoEl = null;
        };
        document.addEventListener('visibilitychange', onHidden);
        scan.removeVisibilityHook = function () {
            document.removeEventListener('visibilitychange', onHidden);
        };
    }

    var promise = new Promise(function (resolve, reject) {
        scan.resolve = resolve;
        scan.reject = reject;

        if (!scan.container) {
            showScanMessage('Scanner area was not found on this page. Refresh and try again.');
            window._activeScan = null;
            reject('Scanner area not found');
            return;
        }

        if (!window.isSecureContext) {
            showFallback(scan, 'Camera is blocked because this site is not being served over a secure HTTPS connection on this device. Open the site using https://, then scan again. In the meantime you can scan a barcode from a photo.', { photo: true, https: true });
            return;
        }
        if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
            showFallback(scan, 'This browser does not support in-page camera scanning. You can scan a barcode from a photo instead.', { photo: true });
            return;
        }

        startLiveScanner(scan, 0, null);
    });

    window.stopScanBarcode = function () {
        stopScan(scan, 'Cancelled');
    };

    return promise;
};
