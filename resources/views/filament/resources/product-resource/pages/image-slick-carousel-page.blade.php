@php use App\Filament\Resources\ProductResource; @endphp
<html>
<head>
    <!-- Add the slick-theme.css if you want default styling -->
    @livewireStyles
    <link rel="stylesheet" type="text/css" href="//cdn.jsdelivr.net/npm/slick-carousel@1.8.1/slick/slick.css"/>
    <!-- Add the slick-theme.css if you want default styling -->
    <link rel="stylesheet" type="text/css" href="//cdn.jsdelivr.net/npm/slick-carousel@1.8.1/slick/slick-theme.css"/>
</head>
<body>
<div>
    @php
        $images = ProductResource::getMedias($this->getRecord()->id);
    @endphp
</div>

<div class="container" wire:ignore id="carousel">

    <div class="slider-for">
        @foreach ($images as $item)
            <img class="block aspect-square rounded-lg" src="{{ $item->downloaded_url }}">
        @endforeach
    </div>

    <div class="slider-nav top-4">
        @foreach ($images as $item)
            <img class="block aspect-square rounded-lg p-0.5" src="{{ $item->downloaded_url }}">
        @endforeach
    </div>
</div>
@livewireScripts
<script type="text/javascript" src="//code.jquery.com/jquery-1.11.0.min.js"></script>
<script type="text/javascript" src="//code.jquery.com/jquery-migrate-1.2.1.min.js"></script>
<script type="text/javascript" src="//cdn.jsdelivr.net/npm/slick-carousel@1.8.1/slick/slick.min.js"></script>
{{--
<script type="text/javascript" src="slick/slick.min.js"></script>
--}}

<script type="text/javascript">
    $(document).ready(function () {
        slider();
    });
</script>
@push('scripts')
    <script>
        document.addEventListener('livewire:load', function () {
            window.livewire.hook('afterDomUpdate', () => {
                slider();
            });
        });

        function slider() {
            $('.slider-for').slick({
                slidesToShow: 1,
                slidesToScroll: 1,
                arrows: false,
                fade: true,
                asNavFor: '.slider-nav'
            });
            $('.slider-nav').slick({
                slidesToShow: 3,
                slidesToScroll: 1,
                asNavFor: '.slider-for',
                dots: true,
                centerMode: true,
                focusOnSelect: true,
                arrows: false
            });
        }
    </script>
@endpush
</body>
</html>
