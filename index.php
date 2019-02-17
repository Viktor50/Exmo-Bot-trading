<div id="main" class="row padding-l padding-r">
  <div class="row-content buffer-bottom clear-after">

    <div class="mr-menu-home">
      <div class="pull-left margin-l margin-b mr-bold" style="text-align: left;">
        <img src="images/stoks_logo/Exmo.png" class="margin-r" style="height: 1.2rem; float: left;"
             alt="logo">Exmo
        <div id="my_balance">
          @foreach($my_crypto_balans as $key => $crypto_balans)
          <li class="mr-small">{{ $key }} - {{ round($crypto_balans, 5) }}</li>
          @endforeach
        </div>
        <hr>
      </div>
      <div class="mr-home-head-links">
        <b><a href="/stoks"><b>Отчёт</b></a></b>
      </div>
      <a href="/panel/analytics" title="Статистика посещения формы и оплаты">
        <div class="mr-home-head-links">
          <b>Арбитраж</b>
        </div>
      </a>
      <a href="/panel/account" title="Настройки аккаунта">
        <div class="mr-home-head-links">
          <b>Аккаунт</b>
        </div>
      </a>
      <div class="mr-small">Выделение памяти: <i id="memory">-</i>
        <span class="margin-l">Пик: </span><i id="memory_pic">-</i>
      </div>
    </div>


    <div class="row">
      <div class="pull-left" style="margin: 0;">
        <div class="mr-small mr-color-seryj margin-l">Книга ордеров: <span id="time_orders"></span></div>
        <div class="mr-small mr-color-seryj margin-l">Продажа/покупка: <span id="different"></span></div>
        <select class="mr-input mr-small margin-l" id="crypto_pair"
                style="float: left; margin-right: 5px; margin-top: 3px; border-radius: 5px;"
                title="выберите платёжную систему">
          <option value=""><span style="size: 0.7rem">[не выбрано]</span></option>
          @foreach($pair_list as $key => $row)
          <option value="{{ $key }}"><span style="size: 0.7rem">{{ $row }}</span></option>
          @endforeach
        </select>
        <span class="mr-btn-pair mr-small mr-background-serij-light mr-color-green" onclick="startUpdate()">Go</span>
        <span class="mr-btn-pair mr-small mr-background-serij-light red" onclick="stopUpdate()">Stop</span>
      </div>
    </div>
    <table>
      <tr class="mr-font-dinamic" style="border-bottom: 1px solid #f3e6c5; margin: 0">
        <td class="mr-color-red mr-bold" style="border-right: black 1px solid" colspan="3">Продажа</td>
        <td class="mr-color-green mr-bold" style="border-right: black 1px solid;" colspan="3">
          Покупка
        </td>
        <td class="mr-color-seryj mr-bold" colspan="4">История торгов <span class="mr-small" id="time_now"></span>
        </td>
      </tr>
      <tr class="mr-background-serij" style="border-bottom: #383838 solid 1px">
        <td class="mr-small mr-color-red">Сумма</td>
        <td class="mr-small mr-color-red">Цена</td>
        <td class="mr-small mr-color-red" style="border-right: black 1px solid">Объём</td>
        <td class="mr-small mr-color-green">Сумма</td>
        <td class="mr-small mr-color-green">Цена</td>
        <td class="mr-small mr-color-green" style="border-right: black 1px solid">Объём</td>
        <td class="mr-small">Сумма</td>
        <td class="mr-small">Цена</td>
        <td class="mr-small">Объём</td>
        <td class="mr-small">Время</td>
      </tr>
      @foreach($order_book as $key => $row_out)
      <tr style="border-bottom: 1px solid #f3e6c5;">
        <td class="mr-small" id="amount_ask_{{ $key }}"></td>
        <td class="mr-small" id="price_ask_{{ $key }}"></td>
        <td class="mr-small" id="weight_ask_{{ $key }}" style="border-right: black 1px solid"></td>
        <td class="mr-small" id="amount_bid_{{ $key }}" style="max-width: 10px"></td>
        <td class="mr-small" id="price_bid_{{ $key }}" style="max-width: 10px"></td>
        <td class="mr-small" id="weight_bid_{{ $key }}" style="max-width: 10px;border-right: black 1px solid"></td>
        @if($row_out['history_kind'] == 2)
        <td class="mr-small mr-color-green" id="history_amount_{{ $key }}"></td>
        <td class="mr-small mr-color-green" id="history_price_{{ $key }}"></td>
        <td class="mr-small mr-color-green" id="history_weight_{{ $key }}"></td>
        <td class="mr-small mr-color-green" id="history_date_{{ $key }}"></td>
        @else
        <td class="mr-small mr-color-red" id="history_amount_{{ $key }}"></td>
        <td class="mr-small mr-color-red" id="history_price_{{ $key }}"></td>
        <td class="mr-small mr-color-red" id="history_weight_{{ $key }}"></td>
        <td class="mr-small mr-color-red" id="history_date_{{ $key }}"></td>
        @endif

      </tr>
      @endforeach
    </table>

    <div class="row inline-block">
      <div class="column five">
        <h4 class="mr-font-dinamic">Открытые ордера <span class="mr-small mr-color-seryj"
                                                          id="time_open_orders"></span></h4>
        <div id="my_open_order">
          <table>
            <tr class="mr-background-serij" style="border-bottom: 1px solid #f3e6c5; margin: 0">
              <td class="mr-td mr-small">Пара</td>
              <td class="mr-td mr-small">Дата/время</td>
              <td class="mr-td mr-small">Операция</td>
              <td class="mr-td mr-small">Цена</td>
              <td class="mr-td mr-small">Количество</td>
              <td class="mr-td mr-small">Сумма</td>
            </tr>

            @foreach($open_orders as $pair => $order)
            @foreach($order as $key => $item)
            <tr>
              <td class="mr-td mr-small">{{ str_replace('_','/',$pair) }}</td>
              <td class="mr-td mr-small">{{ $item['created'] }}</td>
              <td class="mr-td mr-small">
                {{ $item['type'] == 'buy' ? 'Покупка' : 'Продажа' }}
              </td>
              <td class="mr-td mr-small">{{ $item['price'] }}</td>
              <td class="mr-td mr-small">{{ $item['quantity'] }}</td>
              <td class="mr-td mr-small">{{ $item['amount'] }}</td>
            </tr>
            @endforeach
            @endforeach
          </table>
        </div>
      </div>

      <div class="column five">
        <h4 class="mr-font-dinamic">Исторя сделок
          <span class="mr-small mr-color-seryj" id="time_open_orders"></span></h4>
        <div id="table_ok">
          <table>
            <tr class="mr-background-serij" style="border-bottom: 1px solid #f3e6c5; margin: 0">
              <td class="mr-td mr-small">Тип</td>
              <td class="mr-td mr-small">Дата/время</td>
              <td class="mr-td mr-small">Количество</td>
              <td class="mr-td mr-small">Цена</td>
              <td class="mr-td mr-small">Сумма</td>
            </tr>
          </table>
        </div>
      </div>
    </div>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <script src="/public/js/mr_exmo_bot.js"></script>
  </div>
</div>
