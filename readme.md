# shopware edc middleware

## commands
* resource files
  * `php artisan rf:house-keeping:delete-unused-locals`
  * `php artisan rf:house-keeping:force-delete-soft-deleted`
  * `php artisan rf:house-keeping:queue-non-cloud-for-upload`
* edc
  * `php artisan edc:fetch-feed`
  * `php artisan edc:extract-categories`
  * `php artisan edc:export-orders`
* sw
  * `php artisan sw:export-articles`
  * `php artisan sw:fetch-orders`
  * `php artisan sw:update-orders`

## order update endpoint for edc
`/order-update?auth=$configuredAuthToken`

## product category mapping
default expected mapping csv file path: storage/app/cat-mapping.csv
