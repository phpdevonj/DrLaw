<x-frontend-layout :assets="$assets ?? []">
    
    <div class="container pages">
         <h2 class="display-6">{{ ucwords($page->title) }}</h2>
        {!! $page->description !!}
    </div>

</x-frontend-layout>