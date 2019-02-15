window.onload = function () {
  // Текущее время
  setInterval(function () {
    $("#time_now").text(new Date().toLocaleTimeString());
  }, 800);
};

function startUpdate() {
 let id = setInterval(updateData, 5 * 1000);
}

function stopUpdate() {
  clearInterval(id);
}

function updateData() {
  let pair = $('#crypto_pair').val();
  let text_out = '';
  $.ajax({
    headers: {
      'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    },
    enctype: 'multipart/form-data',
    type: 'POST',
    url: '/panel/exmo/update/' + pair,
    data: null,
    contentType: false,
    processData: false,
    success: function (result) {
      // Баланс аккаунта
      for (let key in result['balance']) {
        let out = String(result['balance'][key]);
        text_out += '<li class="mr-small">' + key + ' - ' + out.substring(0, 8) + '</li>';
        $("#my_balance").html(text_out);
      }

      // Выделение памяти
      $("#memory").text(result['memory']);
      let memory_pic = $("#memory_pic");
      if (memory_pic.text() < result['memory'])
        memory_pic.text(result['memory']);

      // Разница общая
      let different = result['orders'][0]['price_ask'] * 100 / result['orders'][0]['price_bid'] - 100;
      let color;
      if (different < 1.4)
        color = 'red';
      else
        color = 'mr-color-green';

      $("#different").html('<span class="' + color + '">' + String(different).substring(0, 3) + '%</span>');

      //// Книга ордеров
      let orders = result['orders'];
      orders.forEach(function (item, i, arr) {
        // Продажа
        $("#amount_ask_" + i).text(String(item['amount_ask']).substring(0, 8));
        $("#price_ask_" + i).text(String(item['price_ask']).substring(0, 8));
        $("#weight_ask_" + i).text(String(item['weight_ask']).substring(0, 8));
        // Покупка
        $("#amount_bid_" + i).text(String(item['amount_bid']).substring(0, 8));
        $("#price_bid_" + i).text(String(item['price_bid']).substring(0, 8));
        $("#weight_bid_" + i).text(String(item['weight_bid']).substring(0, 8));
        // История торгов (стакан)
        let color;
        if (item['history_kind'] === 1)
          color = 'mr-color-red';
        else
          color = "mr-color-green";

        $("#history_price_" + i).html('<span class="' + color + '">' + String(item['history_price']).substring(0, 8) + '</span>');
        $("#history_weight_" + i).html('<span class="' + color + '">' + String(item['history_weight']).substring(0, 8) + '</span>');
        $("#history_amount_" + i).html('<span class="' + color + '">' + String(item['history_amount']).substring(0, 8) + '</span>');
        let date = new Date(item['history_date'] * 1000);
        let formattedDate = ('0' + date.getHours()).slice(-2) + ':' + ('0' + date.getMinutes()).slice(-2) + ':' + ('0' + date.getSeconds()).slice(-2);
        $("#history_date_" + i).html('<span class="' + color + '">' + formattedDate + '</span>');
      });
      $("#time_orders").text(new Date().toLocaleTimeString());

      //// Открытые ордера
      let table_header = '<table style="width: auto">' +
        '<tr class="mr-background-serij" style="border-bottom: 1px solid #f3e6c5; margin: 0">' +
        '<td class="mr-td mr-small">Пара</td>' +
        '<td class="mr-td mr-small">Дата/время</td>' +
        '<td class="mr-td mr-small">Операция</td>' +
        '<td class="mr-td mr-small">Цена</td>' +
        '<td class="mr-td mr-small">Количество</td>' +
        '<td class="mr-td mr-small">Сумма</td>' +
        '</tr>';

      result['open_orders'].forEach(function (item) {
        let type_order = item['type'] === "sell" ? 'Продажа' : 'Покупка';

        let date = new Date(item['created'] * 1000);
        let formattedDate = ('0' + date.getHours()).slice(-2) + ':' + ('0' + date.getMinutes()).slice(-2) + ':' + ('0' + date.getSeconds()).slice(-2);

        table_header += '<tr>' +
          '<td class="mr-td mr-small">' + item['pair'].replace('_', '/') + '</td>' +
          '<td class="mr-td mr-small">' + formattedDate + '</td>' +
          '<td class="mr-td mr-small">' + type_order + '</td>' +
          '<td class="mr-td mr-small">' + String(item['price']).substring(0, 8) + '</td>' +
          '<td class="mr-td mr-small">' + String(item['quantity']).substring(0, 8) + '</td>' +
          '<td class="mr-td mr-small">' + String(item['amount']).substring(0, 8) + '</td>' +
          '</tr>';
      });

      table_header += '</table>';
      $("#my_open_order").html(table_header);
      $("#time_open_orders").text(new Date().toLocaleTimeString());

      let table_ok = '<table style="width: auto">';
      table_ok += '<tr class="mr-background-serij" style="border-bottom: 1px solid #f3e6c5; margin: 0">' +
        '<td class="mr-td mr-small">Тип</td>' +
        '<td class="mr-td mr-small">Дата/время</td>' +
        '<td class="mr-td mr-small">Количество</td>' +
        '<td class="mr-td mr-small">Цена</td>' +
        '<td class="mr-td mr-small">Сумма</td>' +
        '</tr>';

      for (let key in result['ok_trades']) {
        let color;
        if (result['ok_trades'][key]['type'] === 'sell') {
          color = 'mr-color-red';
          text_out = 'Продажа';
        } else {
          color = "mr-color-green";
          text_out = 'Покупка';
        }

        let date = new Date(result['ok_trades'][key]['date'] * 1000);
        let formattedDate = ('0' + date.getHours()).slice(-2) + ':' + ('0' + date.getMinutes()).slice(-2) + ':' + ('0' + date.getSeconds()).slice(-2);


        table_ok += '<tr class="' + color + '">' +
          '<td class="mr-td mr-small">' + text_out + '</td>' +
          '<td class="mr-td mr-small">' + formattedDate + '</td>' +
          '<td class="mr-td mr-small">' + String(result['ok_trades'][key]['quantity']).substring(0, 8) + '</td>' +
          '<td class="mr-td mr-small">' + String(result['ok_trades'][key]['price']).substring(0, 8) + '</td>' +
          '<td class="mr-td mr-small">' + String(result['ok_trades'][key]['amount']).substring(0, 8) + '</td>' +
          '</tr>';
      }

      table_ok += '</table>';

      $("#table_ok").html(table_ok);
    }
  });
}