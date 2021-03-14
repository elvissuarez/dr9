jQuery("document").ready(function () {
  var urlParams = new URLSearchParams(window.location.search);
  var selected = urlParams.get('sort_products');
  if (selected) {
    jQuery("#sort_products").val(selected);
  }
  //ejecutamos el Ordenamieto cuando se selecciona la opcion
  jQuery("#sort_products").change(function () {
     var optionSelected = jQuery("option:selected", this);
     var valueSelected = this.value;
     arr_submit_options = {
       "created": {
         "field": "created",
         "order": "DESC"
       },
       "price_number_asc": {
         "field": "number",
         "order": "ASC"
       },
       "price_number_desc": {
         "field": "number",
         "order": "DESC"
       },
       "title_az": {
         "field": "product_title_sort",
         "order": "ASC"
       },
       "title_za": {
         "field": "product_title_sort",
         "order": "DESC"
       },
      }
      jQuery('#sort_products_form').submit(function () {
        jQuery("#sort_by", this).val(arr_submit_options[valueSelected].field);
        jQuery("#sort_order", this).val(arr_submit_options[valueSelected].order);
        return true;
      });
      jQuery('#sort_products_form').submit();
  });
});
