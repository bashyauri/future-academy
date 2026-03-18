<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| MCP Server Routes
|--------------------------------------------------------------------------
|
| These routes are used by the Model Context Protocol server for
| integration with AI tools and external services
|
*/

Route::prefix('mcp')->group(function () {
    Route::post('/initialize', 'App\Http\Controllers\McpController@initialize');
    Route::post('/call-tool', 'App\Http\Controllers\McpController@callTool');
    Route::post('/list-resources', 'App\Http\Controllers\McpController@listResources');
    Route::post('/read-resource', 'App\Http\Controllers\McpController@readResource');
    Route::get('/server-info', 'App\Http\Controllers\McpController@serverInfo');
});
