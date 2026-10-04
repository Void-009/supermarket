@extends('layouts.main')

@section('title', 'Privacy Policy')

@section('content')

{{-- Hero --}}
<section class="bg-gradient-to-br from-emerald-600 to-emerald-700 text-white py-16 lg:py-20">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="max-w-3xl mx-auto text-center">
            <h1 class="text-3xl sm:text-4xl lg:text-5xl font-extrabold leading-tight" style="font-family:'Poppins',sans-serif;">Privacy Policy</h1>
            <p class="mt-6 text-emerald-100 text-lg leading-relaxed">How M-DELIGHT collects, uses, and protects your personal information.</p>
        </div>
    </div>
</section>

{{-- Policy --}}
<section class="py-16 lg:py-20 bg-gray-50">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="bg-white rounded-2xl p-8 lg:p-12 shadow-sm border border-gray-100 space-y-10">

            <div class="prose prose-emerald max-w-none text-gray-600 leading-relaxed space-y-6">
                <div>
                    <h2 class="text-xl font-bold text-gray-900 mb-3" style="font-family:'Poppins',sans-serif;">1. Information We Collect</h2>
                    <p>When you create an account or place an order, we collect the information you provide directly, including your name, email address, phone number, delivery address, and order details. We also collect limited technical information needed to operate the app, such as your device platform and push-notification subscription token.</p>
                </div>

                <div>
                    <h2 class="text-xl font-bold text-gray-900 mb-3" style="font-family:'Poppins',sans-serif;">2. How We Use Your Information</h2>
                    <p>We use your information to process and deliver your orders, manage your account, send order updates and support messages, respond to your enquiries and complaints, and improve our products and services. We never sell your personal information to third parties.</p>
                </div>

                <div>
                    <h2 class="text-xl font-bold text-gray-900 mb-3" style="font-family:'Poppins',sans-serif;">3. Payments</h2>
                    <p>Payment details for online transactions are processed by our payment service providers. We do not store your card details on our servers.</p>
                </div>

                <div>
                    <h2 class="text-xl font-bold text-gray-900 mb-3" style="font-family:'Poppins',sans-serif;">4. Data Sharing</h2>
                    <p>We share information only with service providers that help us deliver our service (for example, delivery agents and payment processors), and only to the extent necessary. We may also disclose information where required by law.</p>
                </div>

                <div>
                    <h2 class="text-xl font-bold text-gray-900 mb-3" style="font-family:'Poppins',sans-serif;">5. Data Retention &amp; Deletion</h2>
                    <p>We keep your account and order records for as long as your account is active or as required for tax, legal, and accounting purposes. You can delete your account at any time from within the app (Account &rarr; Delete Account). When you delete your account, we remove your personal data and de-identify the records we are legally required to keep.</p>
                </div>

                <div>
                    <h2 class="text-xl font-bold text-gray-900 mb-3" style="font-family:'Poppins',sans-serif;">6. Security</h2>
                    <p>We use appropriate technical and organisational measures to protect your personal information against unauthorised access, alteration, disclosure, or destruction.</p>
                </div>

                <div>
                    <h2 class="text-xl font-bold text-gray-900 mb-3" style="font-family:'Poppins',sans-serif;">7. Your Rights</h2>
                    <p>You may access, update, or correct your personal information at any time through your account settings. You may also request deletion of your data as described above.</p>
                </div>

                <div>
                    <h2 class="text-xl font-bold text-gray-900 mb-3" style="font-family:'Poppins',sans-serif;">8. Contact Us</h2>
                    <p>If you have any questions about this policy or your personal data, please contact us through the <a href="{{ route('contact') }}" class="text-emerald-600 font-semibold hover:underline">contact page</a>.</p>
                </div>

                <p class="text-sm text-gray-400 pt-4 border-t border-gray-100">Last updated: {{ now()->format('F j, Y') }}</p>
            </div>

        </div>
    </div>
</section>

@endsection
