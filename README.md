# Exmo-Bot-trading

Exmo bot for trading. I used Laravel framework.

Route (web.php):
// Page 
Route::get('/panel/exmo', 'Payment\MrExmoBotController@View'); 
// Update page (using JQuery) 
Route::match(['get', 'post'],'/panel/exmo/update/{name}', 'Payment\MrExmoBotController@ApiExmoUpdate');

If you have an interesting idea, let me know. allximik50@gmail.com
