!function(e){"use strict";e(document).ready((function(){e(".noptin-helper-activate-license-modal").on("click",(function(n){n.preventDefault();var t={product_id:e(this).data("id"),_wpnonce:noptin_helper.rest_nonce},o=e(this).data("activating");Swal.fire({titleText:noptin_helper.activate_license,showCancelButton:!0,confirmButtonColor:"#3085d6",cancelButtonColor:"#d33",confirmButtonText:noptin_helper.activate,cancelButtonText:noptin_helper.cancel,showLoaderOnConfirm:!0,showCloseButton:!0,input:"text",inputPlaceholder:noptin_helper.license_key,footer:o,allowOutsideClick:function(){return!Swal.isLoading()},inputValidator:function(e){if(!e)return noptin_helper.license_key},preConfirm:function(e){return t.license_key=e,jQuery.post(noptin_helper.license_activate_url,t).done((function(){Swal.fire({position:"top-end",icon:"success",title:noptin_helper.license_activated,showConfirmButton:!1,timer:1500}),window.location=window.location})).fail((function(e){var n=e.statusText;e.responseJSON&&e.responseJSON.message&&(n=e.responseJSON.message),Swal.fire({icon:"error",title:n,footer:"<code>"+noptin_helper.license_activation_error+"</code>",showCloseButton:!0,confirmButtonText:noptin_helper.close,confirmButtonColor:"#9e9e9e",showConfirmButton:!1})})),jQuery.Deferred()}})})),e(".noptin-helper-deactivate-license-modal").on("click",(function(n){n.preventDefault();var t={license_key:e(this).data("license_key"),_wpnonce:noptin_helper.rest_nonce};Swal.fire({icon:"warning",titleText:noptin_helper.deactivate_license,showCancelButton:!0,confirmButtonColor:"#3085d6",cancelButtonColor:"#d33",confirmButtonText:noptin_helper.deactivate,cancelButtonText:noptin_helper.cancel,showLoaderOnConfirm:!0,showCloseButton:!0,footer:noptin_helper.deactivate_warning,allowOutsideClick:function(){return!Swal.isLoading()},preConfirm:function(){return jQuery.post(noptin_helper.license_deactivate_url,t).done((function(){Swal.fire({position:"top-end",icon:"success",title:noptin_helper.license_deactivated,showConfirmButton:!1,timer:1500}),window.location=window.location})).fail((function(e){var n=e.statusText;e.responseJSON&&e.responseJSON.message&&(n=e.responseJSON.message),Swal.fire({icon:"error",title:noptin_helper.license_deactivation_error,footer:n,showCloseButton:!0,confirmButtonText:noptin_helper.close,confirmButtonColor:"#9e9e9e",showConfirmButton:!1})})),jQuery.Deferred()}})}))}))}(jQuery);