<!-- Backend Bundle JavaScript -->
<script src="{{ asset('js/backend-bundle.min.js') }}"></script>

<script src="{{ asset('js/raphael-min.js') }}"></script>

<script src="{{ asset('js/morris.js') }}"></script>
<script src="{{ asset('vendor/tinymce/js/tinymce/tinymce.min.js') }}"></script>
<script src="{{ asset('vendor/confirmJS/jquery-confirm.min.js') }}"></script>
<script src="{{ asset('js/jquery.validate.min.js') }}"></script>
<script>
    // Text Editor code
      if (typeof(tinyMCE) != "undefined") {
         // tinymceEditor()
         function tinymceEditor(target, button, height = 200) {
            var rtl = $("html[lang=ar]").attr('dir');
            tinymce.init({
               selector: target || '.textarea',
               directionality : rtl,
               height: height,
               plugins: [ 'advlist', 'autolink', 'lists', 'link', 'image', 'charmap', 'preview', 'anchor', 'searchreplace', 'visualblocks', 'code', 'fullscreen', 'insertdatetime', 'media', 'table', 'help', 'wordcount' ],
               toolbar: 'undo redo | blocks | bold italic backcolor | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | removeformat | help',
               content_style: 'body { font-family:Helvetica,Arial,sans-serif; font-size:16px }',
               automatic_uploads: false,
               /*file_picker_types: 'image',
               file_picker_callback: function(cb, value, meta) {
                  var input = document.createElement('input');
                  input.setAttribute('type', 'file');
                  input.setAttribute('accept', 'image/*');

                  input.onchange = function() {
                     var file = this.files[0];

                     var reader = new FileReader();
                     reader.onload = function() {
                        var id = 'blobid' + (new Date()).getTime();
                        var blobCache = tinymce.activeEditor.editorUpload.blobCache;
                        var base64 = reader.result.split(',')[1];
                        var blobInfo = blobCache.create(id, file, base64);
                        blobCache.add(blobInfo);

                        cb(blobInfo.blobUri(), { title: file.name });
                     };
                     reader.readAsDataURL(file);
                  };
                  input.click();
               }*/
            });
         }
      }
      function showCheckLimitData(id){
         var checkbox =  $('#'+id).is(":checked")
         if(checkbox == true){
            $('.'+id).removeClass('d-none')
         }else{
            $('.'+id).addClass('d-none')

         }
      }

      $('#submit-btn').on('click', function(e) {
         e.preventDefault();

             $('#button-loader').show();
             $('#submit-btn').prop('disabled', true);

             setTimeout(function() {
                 $('form').submit();
             }, 1000);
     });

      function formValidation(formId, rules, messages) {
         $(formId).validate({
            rules: rules,
            messages: messages,
            errorClass: "help-block error",
            highlight: function(element) {
               $(element).closest(".form-group.row").addClass("has-error");
            },
            unhighlight: function(element) {
               $(element).closest(".form-group.row").removeClass("has-error");
            },
            errorPlacement: function(error, element) {
               if (element.hasClass('select2js')) {
                  error.insertAfter(element.next('.select2-container'));
               } else {
                  error.insertAfter(element);
               }
            }
         });
         $('.select2js').on('change', function() {
            $(this).valid();
         });
     }
</script>
@if(isset($assets) && in_array('map', $assets))
    <script src="https://maps.googleapis.com/maps/api/js?key={{env('GOOGLE_MAP_KEY')}}&v=3.64&libraries=drawing" defer></script>
@endif

@if(isset($assets) && in_array('map_place', $assets))
   <script src="https://maps.googleapis.com/maps/api/js?key={{env('GOOGLE_MAP_KEY')}}&v=3.64&libraries=places" defer></script>
@endif

@yield('bottom_script')

<!-- Masonary Gallery Javascript -->
<script src="{{ asset('js/masonry.pkgd.min.js') }}"></script>
<script src="{{ asset('js/imagesloaded.pkgd.min.js') }}"></script>

<!-- Vectoe Map JavaScript -->
<script src="{{ asset('js/vector-map-custom.js') }}"></script>

<!-- Chart Custom JavaScript -->
<script src="{{ asset('js/customizer.js') }}"></script>

<!-- Chart Custom JavaScript -->
<script src="{{ asset('js/chart-custom.js') }}"></script>

<!-- slider JavaScript -->
<script src="{{ asset('js/slider.js') }}"></script>

<!-- Emoji picker -->
<script type="module" src="{{ asset('vendor/emoji-picker-element/index.js') }}"></script>

@if(isset($assets) && (in_array('datatable',$assets)))
<!-- <script src="{{ asset('vendor/datatables/js/jquery.dataTables.min.js') }}"></script> -->
<!-- <script src="{{ asset('vendor/datatables/js/dataTables.bootstrap4.min.js') }}"></script> -->
<!-- <script src="{{ asset('vendor/datatables/js/dataTables.buttons.min.js') }}"></script> -->
<!-- <script src="{{ asset('vendor/datatables/js/buttons.bootstrap4.min.js') }}"></script> -->
<script src="{{ asset('vendor/datatables/buttons.server-side.js') }}"></script>
<!-- <script src="{{ asset('vendor/datatables/js/dataTables.select.min.js') }}"></script> -->
@endif

<!-- app JavaScript -->
@if(isset($assets) && in_array('phone', $assets))
    <script src="{{ asset('vendor/intlTelInput/js/intlTelInput-jquery.min.js') }}"></script>
    <script src="{{ asset('vendor/intlTelInput/js/intlTelInput.min.js') }}"></script>
@endif

<script src="{{ asset('js/app.js') }}" defer></script>
<script src="{{ asset('js/sweetalert.min.js')}}"></script>
@include('helper.app_message')

{{-- Google Translate: hidden widget + custom switcher logic --}}
<style>
/* Hide the Google top frame that pushes the page down */
body > .skiptranslate { display: none !important; }
body { top: 0px !important; }

/* Custom language switcher dropdown */
.og-lang-menu {
    display: none;
    position: absolute;
    top: calc(100% + 8px);
    right: 0;
    left: auto;
    min-width: 160px;
    background: #fff;
    border: 1px solid rgba(0,0,0,.1);
    border-radius: 6px;
    box-shadow: 0 6px 24px rgba(0,0,0,.12);
    z-index: 9999;
}
.og-lang-menu.open {
    display: block;
}
.og-lang-menu .list-group-item {
    border-left: 0;
    border-right: 0;
    font-size: 14px;
}
.og-lang-menu .list-group-item:first-child {
    border-top: 0;
    border-top-left-radius: 6px;
    border-top-right-radius: 6px;
}
.og-lang-menu .list-group-item:last-child {
    border-bottom: 0;
    border-bottom-left-radius: 6px;
    border-bottom-right-radius: 6px;
}
.og-lang-menu .list-group-item:hover,
.og-lang-menu .lang-option.active {
    background: #f0f0f0;
}
</style>
<script type="text/javascript">
    // 1. Initialize Google Translate
    function googleTranslateElementInit() {
        new google.translate.TranslateElement({
            pageLanguage: 'en',
            // Comma-separated list of languages you want to support
            // includedLanguages: 'en,fr',
            layout: google.translate.TranslateElement.InlineLayout.SIMPLE,
            autoDisplay: false
        }, 'google_translate_element');
    }

    // 2. Aggressively clear old cookies to prevent getting stuck
    function clearGoogTransCookie() {
        var d = location.hostname.split('.');
        var domains = [''];
        domains.push('; domain=' + location.hostname);
        domains.push('; domain=.' + location.hostname);
        while (d.length > 1) {
            var dom = d.join('.');
            domains.push('; domain=' + dom);
            domains.push('; domain=.' + dom);
            d.shift();
        }
        domains.forEach(function(dom) {
            document.cookie = 'googtrans=; path=/; expires=Thu, 01 Jan 1970 00:00:01 GMT' + dom;
        });
    }

    // 3. Set new cookie
    function setGoogTransCookie(lang) {
        clearGoogTransCookie();
        if (lang && lang !== 'en') {
            var domain = location.hostname;
            var val = '/en/' + lang;
            document.cookie = 'googtrans=' + val + '; path=/; expires=Thu, 01 Jan 2099 00:00:00 GMT';
            if (domain.indexOf('.') !== -1 && !/^\d{1,3}(\.\d{1,3}){3}$/.test(domain)) {
                document.cookie = 'googtrans=' + val + '; path=/; expires=Thu, 01 Jan 2099 00:00:00 GMT; domain=.' + domain;
            }
        }
    }

    // 4. Read active language on page load
    function getActiveLang() {
        var m = decodeURIComponent(document.cookie).match(/(?:^|;)\s*googtrans=\/en\/([^;]+)/);
        return m ? m[1] : 'en';
    }

    // 5. Update UI to match active language
    function syncUI(lang) {
        var opt = document.querySelector('.lang-option[data-code="' + lang + '"]');
        if (!opt) return;
        var flag = document.getElementById('selected-lang-flag');
        var label = document.getElementById('selected-lang-label');
        if (flag) flag.src = opt.dataset.flag;
        if (label) label.textContent = opt.dataset.name;
        document.querySelectorAll('.lang-option').forEach(function(el) {
            el.classList.toggle('active', el.dataset.code === lang);
        });
    }

    // 6. Handle selection
    function selectLanguage(code) {
        setGoogTransCookie(code);
        location.reload(); // Reload to let Google Translate read the new cookie
    }

    // 7. Event Listeners
    document.addEventListener('DOMContentLoaded', function () {
        syncUI(getActiveLang());

        var switcher = document.getElementById('custom-lang-switcher');
        var toggleBtn = document.getElementById('lang-toggle-btn');
        var menu = document.getElementById('lang-menu');

        if (!switcher || !toggleBtn || !menu) return;

        // Open/close dropdown
        toggleBtn.addEventListener('click', function (e) {
            e.preventDefault();
            e.stopPropagation();
            menu.classList.toggle('open');
        });

        // Click an option
        menu.addEventListener('click', function (e) {
            e.stopPropagation();
            var opt = e.target.closest('.lang-option');
            if (opt) selectLanguage(opt.dataset.code);
        });

        // Click outside to close
        document.addEventListener('click', function (e) {
            if (!switcher.contains(e.target)) {
                menu.classList.remove('open');
            }
        });
    });
</script>
<script type="text/javascript" src="//translate.google.com/translate_a/element.js?cb=googleTranslateElementInit"></script>
