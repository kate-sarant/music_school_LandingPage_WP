


var el_string_not_paying = `<div style="margin-top:10px"><label  class="inline-flex items-center "><input type="checkbox" disabled class="form-radio" name="" value="1"><span class="ml-2">Remove Date (Removes date from Google search result)</span></label> <a href="${obj.upgradeUrl}"><span class="bg-yellow-700 text-gray-200 text-sm py-1 px-1 rounded-md">Premium</span></a></div>`;

var el_string_paying_turned_off_yoast = `<div style="margin-top:10px">Date can be removed from WP Meta and Date Remover settings</div>`

var el_string_paying_turned_on_yoast = `<div style="margin-top:10px">Date removed by WP Meta and Date Remover Pro</div>`

var el = null;

if(obj.isPaying){
    if(obj.isYoastRemoveEnabled){
        el = jQuery.parseHTML(el_string_paying_turned_on_yoast);
    }
    else{
        el = jQuery.parseHTML(el_string_paying_turned_off_yoast);
    }
}
else{
    el = jQuery.parseHTML(el_string_not_paying);
}



jQuery(document).ready(function(){

    var checkExist = setInterval(function() {
        if (jQuery('#yoast-snippet-preview-container').length) {
           
jQuery(el).insertAfter("#yoast-snippet-preview-container");
           clearInterval(checkExist);
        }
     }, 1000);

})