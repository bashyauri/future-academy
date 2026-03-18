<?php

return [
    /*
     * The default amount of results that will be returned
     * when using the Paginate filter.
     */
    'default_page_size' => 15,

    /*
     * The key that will be used to request a different page when
     * using the Paginate filter.
     */
    'page_key' => 'page',

    /*
     * The key that will be used to request a different sort order when
     * using the Sort filter.
     */
    'sort_key' => 'sort',

    /*
     * The key that will be used to request a different field when
     * using the Fields filter.
     */
    'fields_key' => 'fields',

    /*
     * The key that will be used to request to include relationships when
     * using the Include filter.
     */
    'include_key' => 'include',

    /*
     * The key that will be used to request a different filter when
     * using the Filter filter.
     */
    'filter_key' => 'filter',

    /*
     * The maximum number of results that can be requested.
     */
    'maximum_page_size' => 100,

    /*
     * Set to `false` if you want to disable caching of allowed includes and filters.
     */
    'cache_filters' => true,

    /*
     * Set to `false` if you want to disable the ability to request single or multiple
     * fields from filters.
     */
    'allow_field_filters' => true,
];
