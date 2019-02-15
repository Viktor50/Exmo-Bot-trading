<?php

namespace App\Http\Controllers\Payment;


use App\Http\Controllers\Controller;
use Mockery\Exception;

class MrExmoBotController extends Controller
{
  public $out = array();

  private $base_different = 1; // Percent different sell/buy
  private $min_balance = 1; // minimal balance to start trade
  private $change = 0.000001; // change
  private $exmo_key = "K-...";
  private $exmo_secret_key = "S-...";
  private $UrlApiOrderBook = 'https://api.exmo.com/v1/order_book/?pair=';
  private $UrlApiOrderBookHistory = 'https://api.exmo.com/v1/trades/?pair=';

  const KIND_SELL = 1;
  const KIND_BUY = 2;

  public function View()
  {
    // nothing
    $stok_out_book = array(1, 2, 3, 4, 5);
    $this->out['order_book'] = $stok_out_book;

    // Balance
    $response = $this->api_query('user_info', Array());

    $balance_out_array = array();
    foreach ($response['balances'] as $crypto_name => $balance)
    {
      if((float)$balance > 0.0001)
        $balance_out_array[$crypto_name] = $balance;
    }

    // for select input
    $open_orders = $this->api_query('user_open_orders', Array());
    $pair_list_usd = array(
      'MNX_USD' => 'MNX/USD',
      'LSK_USD' => 'LSK/USD',
      'EOS_USD' => 'EOS/USD',
    );


    $this->out['pair_list'] = $pair_list_usd;
    $this->out['open_orders'] = $open_orders;
    $this->out['my_crypto_balans'] = $balance_out_array;

    return View('panel.exmo_bot')->with($this->out);
  }

  /**
   * Update all data
   *
   * GET or POST (Ajax)
   *
   * @param string $crypto_pair_name
   * @return array
   */
  public function ApiExmoUpdate(string $crypto_pair_name): array
  {
    // Memory in real time
    $out['memory'] = (memory_get_usage(true) / 1024) . ' KB';
    $out['memory_pic'] = (memory_get_peak_usage(true) / 1024) . ' KB';

    // Order book
    $orders = $this->GetOrderBook($crypto_pair_name);
    $out['orders'] = $orders;
    // Account balance
    $out['balance'] = $balance = $this->GetBalance();
    // Open orders list
    $out['open_orders'] = $open_orders = $this->GetOpenOrder();
    // Trade history
    $out['ok_trades'] = $this->GetListOkTrades($crypto_pair_name);

    // Trading
    $this->tradeByBorder($balance, $open_orders, $orders, $crypto_pair_name);

    return $out;
  }

  /**
   * Trading
   *
   * @param array $balance
   * @param array $open_orders_list
   * @param array $order_book
   * @param string $name
   */
  public function tradeByBorder(array $balance, array $open_orders_list, array $order_book, string $name)
  {
    //// Торговля на границах стакана
    $different = (float)$order_book[0]['price_ask'] * 100 / (float)$order_book[0]['price_bid'] - 100;

    $pair_name = $name;
    $pair = explode('_', $name);
    // Cancel all orders, if have too little different
    if($different < $this->base_different)
    {
      foreach ($open_orders_list as $open_order)
      {
        $this->CancelOrder($open_order['order_id']);
      }
      return;
    }

    //// Create new order
    if(count($balance))
    {
      foreach ($balance as $key => $item)
      {
        //// Sell currency
        if($key == $pair[0] && (float)$item > $this->min_balance)
        {
          // Correct order
          foreach ($open_orders_list as $open_order)
          {
            if($pair_name == $open_order['pair'] && $open_order['type'] == 'sell')
            {
              $this->CancelOrder($open_order['order_id']);
              return;
            }
          }
          $this->addOrder((float)$order_book[0]['price_ask'] - $this->change, $name, self::KIND_SELL, $item);
        }
        //// Buy XXX/USD
        if($key == $pair[1] && (float)$item > $this->min_balance)
        {
          // isset opened open order
          foreach ($open_orders_list as $open_order)
          {
            if($pair_name == $open_order['pair'] && $open_order['type'] == 'buy')
            {
              $this->CancelOrder($open_order['order_id']);
              return;
            }
          }
          $quantity = ((float)$item - 0.01) / (float)$order_book[0]['price_bid'];
          $this->addOrder((float)$order_book[0]['price_bid'] + $this->change, $pair_name, self::KIND_BUY, $quantity);
        }
      }
    }

    $open_order = null;
    //// Working with opened order
    foreach ($open_orders_list as $open_order)
    {
      // isset open order
      if($pair_name == $open_order['pair'])
      {
        $order_id = $open_order['order_id'];
        // Is active
        $status_order = $this->IsActual($open_order, $order_book[0]);
        // If false - Update
        if((bool)$status_order === false)
        {
          if($open_order['type'] == 'sell')
          {
            $this->CancelOrder($order_id);
            $this->addOrder($order_book[0]['price_ask'] - $this->change, $pair_name, self::KIND_SELL, $open_order['quantity']);
          }
          else
          {
            $this->CancelOrder($order_id);
            $this->addOrder($order_book[0]['price_bid'] + $this->change, $pair_name, self::KIND_BUY, $open_order['quantity']);
          }
        }
      }
    }
  }

  /**
   * Active?
   *
   * @param array $open_order открытый ордер
   * @param array $order_book книга ордера
   * @return bool
   */
  protected function IsActual(array $open_order, array $order_book): bool
  {
    $kind = $open_order['type'];

    if($kind == 'sell' && $order_book['price_ask'] < $open_order['price'])
      return false;

    if($kind == 'buy' && $order_book['price_bid'] > $open_order['price'])
      return false;

    return true;
  }

#region API to Exmo

  /**
   * Get balance
   *
   * @return mixed
   */
  public function GetBalance()
  {
    $response = $this->api_query('user_info', Array());
    $balance_out_array = array();
    if(isset($response['balances']))
    {
      foreach ($response['balances'] as $crypto_name => $balance)
      {
        if((float)$balance > 0.0001)
          $balance_out_array[$crypto_name] = (float)$balance;
      }
    }
    return $balance_out_array;
  }

  /**
   * Get canceled orders list
   *
   * @return array
   */
  public function GetCanceledTrades(): array
  {
    $method_name = 'user_cancelled_orders';
    $parametres = array("limit" => 10, "offset" => 0);
    $list = $this->api_query($method_name, $parametres);
    $out = array();
    foreach ($list as $trade)
      $out[$trade['trade_id']] = $trade;

    return $out;
  }

  /**
   * Cancel order
   *
   * @param int $id
   */
  public function CancelOrder(int $id)
  {
    $method_name = 'order_cancel';
    $parametres = Array(
      "order_id" => $id,
    );
    $this->api_query($method_name, $parametres);
  }

  /**
   * Get trade list
   *
   * @param string $name
   * @return array
   */
  public function GetListOkTrades(string $name): array
  {
    $method_name = 'user_trades';
    $parameters = array(
      "pair" => $name, "limit" => 7, "offset" => 0
    );

    $list = $this->api_query($method_name, $parameters);
    $out = array();
    foreach ($list[$name] as $trade)
      $out[] = $trade;

    return $out;
  }

  /**
   * Update order book
   *
   * @return array
   */
  public function GetOpenOrder(): array
  {
    $list = $this->api_query('user_open_orders', Array());
    $out = array();
    if(count($list))
    {
      foreach ($list as $row)
      {
        if(count($row))
        {
          foreach ($row as $item)
          {
            $out[] = $item;
          }
        }
      }
    }
    return $out;
  }

  /**
   * Download method from https://github.com/exmo-dev/exmo_api_lib/blob/master/php/exmo.php
   *
   * @param $api_name
   * @param array $req
   * @return mixed
   */
  function api_query($api_name, array $req = array())
  {
    $mt = explode(' ', microtime());
    $NONCE = $mt[1] . substr($mt[0], 2, 6);
    // API settings
    $key = $this->exmo_key;
    $secret = $this->exmo_secret_key;
    $url = "http://api.exmo.com/v1/$api_name";
    $req['nonce'] = $NONCE;
    // generate the POST data string
    $post_data = http_build_query($req, '', '&');
    $sign = hash_hmac('sha512', $post_data, $secret);
    // generate the extra headers
    $headers = array(
      'Sign: ' . $sign,
      'Key: ' . $key,
    );
    // our curl handle (initialize if required)
    static $ch = null;
    if(is_null($ch))
    {
      $ch = curl_init();
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
      curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/4.0 (compatible; PHP client; ' . php_uname('s') . '; PHP/' . phpversion() . ')');
    }
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);

    // run the query
    $res = curl_exec($ch);
    if($res === false) throw new Exception('Could not get reply: ' . curl_error($ch));
    $dec = json_decode($res, true);
    if($dec === null)
      throw new Exception('Invalid data received, please make sure connection is working and requested API exists');

    return $dec;
  }

  /**
   * Create order
   *
   * @param float $price Cost
   * @param $name
   * @param int $type Sell/Buy
   * @param float $quantity Количество
   * @return array
   */
  public function addOrder(float $price, $name, int $type, float $quantity): array
  {
    $method_name = 'order_create';
    $parameters = Array(
      "pair" => $name,
      "quantity" => $quantity,
      "price" => $price,
      "type" => $type == self::KIND_SELL ? 'sell' : 'buy',
    );

    $q = $this->api_query($method_name, $parameters);

    return $q;
  }
#endregion

#region Get data

  /**
   * Get order book
   *
   * @param string $crypto_pair_name
   * @return array
   */
  public function GetOrderBook(string $crypto_pair_name): array
  {
    $postfix = '&limit=5';

    $json = file_get_contents($this->UrlApiOrderBook . $crypto_pair_name . $postfix);
    if(empty($json))
    {
      return array();
    }
    $data = json_decode($json, true);
    if($data == null || !count($data))
    {
      return array();
    }

    foreach ($data[$crypto_pair_name] as $key => $s)
    {
      $order_book_out = array();
      foreach ($data[$crypto_pair_name]['ask'] as $key_2 => $history_data)
      {
        $order_book = array(
          'price_ask' => $history_data[0],
          'weight_ask' => $history_data[1],
          'amount_ask' => $history_data[2],

          'price_bid' => $data[$crypto_pair_name]['bid'][$key_2][0],
          'weight_bid' => $data[$crypto_pair_name]['bid'][$key_2][1],
          'amount_bid' => $data[$crypto_pair_name]['bid'][$key_2][2],
        );

        $order_book_out[] = $order_book;
      }
    }

    //// History
    $json = @file_get_contents($this->UrlApiOrderBookHistory . $crypto_pair_name . $postfix);
    if(empty($json))
      return array();
    $data = json_decode($json, true);
    if($data == null || !count($data))
      return array();
    $history_book = array();
    foreach ($data[$crypto_pair_name] as $s)
    {
      $history_book[] = array(
        'history_kind' => $s['type'] == 'buy' ? 2 : 1,
        'history_weight' => $s['quantity'],
        'history_price' => $s['price'],
        'history_amount' => $s['amount'],
        'history_date' => $s['date'],
      );
    }

    $result_array = array();

    if(isset($order_book_out) && count($order_book_out))
    {
      foreach ($order_book_out as $key => $history_data)
      {
        $result_array[] = array_merge($history_data, $history_book[$key]);
      }
    }

    return $result_array;
  }
#endregion
}