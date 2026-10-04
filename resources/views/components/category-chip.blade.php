@props(['category'])

@php
    $emoji = [
        'food' => '🍽️', 'grocery' => '🛒', 'vegetable' => '🥬', 'veggies' => '🥬',
        'fruit' => '🍎', 'fruits' => '🍎', 'drink' => '🥤', 'drinks' => '🥤',
        'household' => '🧴', 'cleaning' => '🧹', 'detergent' => '🧴', 'kitchen' => '🍳',
        'cooking' => '🍳', 'oil' => '🫒', 'meat' => '🥩', 'fish' => '🐟',
        'bakery' => '🍞', 'bread' => '🍞', 'dairy' => '🥛', 'milk' => '🥛',
        'snack' => '🍿', 'snacks' => '🍿', 'beauty' => '💄', 'baby' => '👶',
        'electronics' => '🔌', 'phone' => '📱', 'stationery' => '✏️', 'toiletries' => '🧼',
        'beverages' => '🥤', 'spices' => '🌶️', 'frozen' => '🧊', 'canned' => '🥫',
        'rice' => '🍚', 'pasta' => '🍝', 'soap' => '🧼', 'wash' => '🧺',
    ];
    $lower = strtolower($category->name);
    $matched = $emoji['grocery'];
    foreach ($emoji as $key => $icon) {
        if (str_contains($lower, $key)) { $matched = $icon; break; }
    }
@endphp

<a href="{{ route('shop.index', ['category' => $category->slug]) }}"
   class="flex flex-col items-center gap-1.5 text-center group shrink-0">
    <span class="w-14 h-14 sm:w-16 sm:h-16 rounded-2xl bg-white border border-gray-100 shadow-sm hover:shadow-md hover:border-emerald-200 flex items-center justify-center text-2xl sm:text-3xl transition-all duration-300 group-hover:-translate-y-0.5">
        {{ $matched }}
    </span>
    <span class="block max-w-[6rem] text-[11px] sm:text-xs font-medium text-gray-700 group-hover:text-emerald-600 transition-colors truncate">{{ $category->title_name }}</span>
</a>
