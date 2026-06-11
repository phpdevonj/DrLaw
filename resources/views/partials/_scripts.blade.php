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
    <script src="https://maps.googleapis.com/maps/api/js?key={{env('GOOGLE_MAP_KEY')}}&libraries=drawing" defer></script>
@endif

@if(isset($assets) && in_array('map_place', $assets))
   <script src="https://maps.googleapis.com/maps/api/js?key={{env('GOOGLE_MAP_KEY')}}&libraries=places" defer></script>
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
<script type="text/javascript">
    // Helper to get cookie value
    function getGoogTransCookieVal() {
        var name = "googtrans=";
        var decodedCookie = decodeURIComponent(document.cookie);
        var ca = decodedCookie.split(';');
        for(var i = 0; i < ca.length; i++) {
            var c = ca[i];
            while (c.charAt(0) == ' ') {
                c = c.substring(1);
            }
            if (c.indexOf(name) == 0) {
                return c.substring(name.length, c.length).replace(/"/g, '');
            }
        }
        return null;
    }

    /* ── 1. Set/Delete googtrans cookie ── */
    // Helper to clear existing cookies on all potential domain/path permutations to prevent duplicate conflicts
    function clearGoogTransCookiePermutations() {
        var domain = location.hostname;
        var expirePast = 'expires=Thu, 01 Jan 1970 00:00:01 GMT; path=/';
        document.cookie = 'googtrans=; path=/; expires=' + expirePast;
        document.cookie = 'googtrans=; path=/; domain=' + domain + '; expires=' + expirePast;
        document.cookie = 'googtrans=; path=/; domain=.' + domain + '; expires=' + expirePast;
        document.cookie = 'googtrans=; expires=' + expirePast;
    }

    function setGoogTransCookie(langCode) {
        var sourceLang = 'en'; // The source HTML of the templates is always English
        var domain = location.hostname;

        // Always clean any existing cookie variants first
        clearGoogTransCookiePermutations();

        var val = '/' + sourceLang + '/' + langCode;
        var cookieStr = 'googtrans=' + val + '; path=/; expires=Thu, 01 Jan 2099 00:00:00 GMT';

        // Add Secure attribute if the site is served over HTTPS
        if (location.protocol === 'https:') {
            cookieStr += '; Secure';
        }

        // Set cookie for exact hostname
        document.cookie = cookieStr;

        // Set cookie with leading dot domain if applicable (non-IP and has dots)
        if (domain.indexOf('.') !== -1 && !/^\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}$/.test(domain)) {
            document.cookie = cookieStr + '; domain=.' + domain;
        }
    }

    /* ── 2. Google Translate widget initialisation ── */
    function googleTranslateElementInit() {
        new google.translate.TranslateElement({
            pageLanguage: 'en', // The source document text is English
            includedLanguages: window._adminLangCodes || 'en,fr,hi',
            layout: google.translate.TranslateElement.InlineLayout.SIMPLE,
            autoDisplay: false
        }, 'google_translate_element');
    }

    /* ── 3. Update the toggle-button UI from a specific language code ── */
    function syncUIFromActiveLanguage() {
        var activeCode = window._defaultLangCode || 'en';
        var cookieVal = getGoogTransCookieVal();
        
        if (cookieVal) {
            var parts = cookieVal.split('/');
            if (parts.length >= 3) {
                activeCode = parts[2];
            }
        }

        // Find the option in our DOM list
        var opt = document.querySelector('#custom-lang-switcher .lang-option[data-code="' + activeCode + '"]');
        if (opt) {
            var name = opt.dataset.name;
            var flag = opt.dataset.flag;
            
            var flagEl  = document.getElementById('selected-lang-flag');
            var labelEl = document.getElementById('selected-lang-label');
            if (flagEl) flagEl.src = flag;
            if (labelEl) labelEl.textContent = name;

            document.querySelectorAll('#custom-lang-switcher .lang-option').forEach(function (el) {
                el.classList.toggle('active', el.dataset.code === activeCode);
            });
        }
    }

    /* ── 4. User picks a language from the custom dropdown ── */
    function selectLanguage(code, name, flag) {
        if (code === 'default') {
            clearGoogTransCookiePermutations();
            var defaultCode = window._defaultLangCode || 'en';
            if (defaultCode !== 'en') {
                setGoogTransCookie(defaultCode);
            }
            location.reload();
            return;
        }

        // Update cookie
        setGoogTransCookie(code);
        // Reload page to let Google Translate apply it cleanly
        location.reload();
    }

    /* ── 5. Wire up dropdown events after DOM ready ── */
    document.addEventListener('DOMContentLoaded', function () {
        var defaultCode = window._defaultLangCode || 'en';
        var cookieVal = getGoogTransCookieVal();

        // If no translation cookie exists and the default language is not English,
        // automatically set the cookie and reload to display the page in the default language.
        if (!cookieVal && defaultCode !== 'en') {
            setGoogTransCookie(defaultCode);
            location.reload();
            return;
        }

        // Sync UI on load
        syncUIFromActiveLanguage();

        var switcher  = document.getElementById('custom-lang-switcher');
        var toggleBtn = document.getElementById('lang-toggle-btn');
        var menu      = document.getElementById('lang-menu');
        var caret     = document.getElementById('lang-caret');

        if (!switcher || !toggleBtn || !menu) return;

        // Toggle button — open / close
        toggleBtn.addEventListener('click', function (e) {
            e.preventDefault();
            e.stopPropagation();
            var isOpen = menu.classList.contains('open');
            menu.classList.toggle('open', !isOpen);
            if (caret) caret.style.transform = isOpen ? 'rotate(0deg)' : 'rotate(180deg)';
        });

        // Language option click
        menu.addEventListener('click', function (e) {
            e.stopPropagation();
            var opt = e.target.closest('.lang-option');
            if (!opt) return;
            selectLanguage(opt.dataset.code, opt.dataset.name, opt.dataset.flag);
        });

        // Close when clicking anywhere outside the switcher
        document.addEventListener('click', function (e) {
            if (switcher && !switcher.contains(e.target)) {
                menu.classList.remove('open');
                if (caret) caret.style.transform = 'rotate(0deg)';
            }
        });
    });
</script>
<script type="text/javascript" src="//translate.google.com/translate_a/element.js?cb=googleTranslateElementInit"></script>
