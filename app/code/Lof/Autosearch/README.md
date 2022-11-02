# Magento 2 Auto Search Suggestion extension:

Call search API by use the url:

http://yourdomain.com/rest/V1/lof-autosearch/autosearch/search-key-world/store_id/category_id/limit_terms

example:
http://newdemo.demo4coder.com/magento2/rest/V1/lof-autosearch/autosearch/Push+it/1/0/5

search-key-world: the keyworld to search (string). If have empty space, please replace it to '+'. Example: Push+it
store_id: current store id (number)
category_id: search products in the category (number)
limit_terms: number of limit suggestion keywords on result (number)