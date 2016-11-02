$(function () {
    responsiveNav();

    $('ul.main-nav a').each(function () {
        if (this.href === location.href) $(this).parent().addClass('active');
    });

});

function responsiveNav() {
    var html = '';

    var cloned = $('.main-nav > li').clone();

    var container = $('<div>', {id: 'responsive-nav'});
    var items = $('<ul>', {id: 'responsive-nav-items'});
    var trigger = $('<div>', {id: 'responsive-nav-trigger', text: 'Navigate...'});

    container.appendTo('#nav .container');
    items.appendTo(container);

    items.append(cloned);

    items.find('li').removeClass('dropdown');
    items.find('ul').removeClass('dropdown-menu');
    items.find('.caret').remove();

    items.append(html);

    trigger.bind('click', function (e) {
        items.slideToggle();
        trigger.toggleClass('open');
    });
    ;

    trigger.prependTo(container);
}
//Обработчик создания апелляций
$(document).ready(function () {
    $("#apillation").click(function () {
		//Дабы 300 раз подряд не щелкали
        $(this).remove();
		//Вместо befroreSend стоковая заглушка от CS:Bans
        $("#loading").show();
		//Сохраняем ссылку на текущий бан
        var link = $("#viewban").attr("href");
		//Для получения ID бана
        var bid = $("#viewban").attr("href");
        if (bid.match(/[^0-9]/g)) {
            bid = bid.replace(/[^0-9]/g, '');
            var data = {
                'bid': bid,
                'link': link
            };
        }
		//Передаем обработчику массив $_POST = data
        $.ajax({
            type: "POST",
            url: "/unban.php",
            dataType: 'JSON',
            data: data,
            success: function (json) {
				//Получили JSON. Если ошибка - тормозим процессб если нет, переходим в тему бана
                if (json.error) {
                    alert(json.error_message);
                    return false;
                }
                var url = 'http://g-nation.ru/index.php?/topic/' + json.topic_id;
                window.location.href = url;
            },
            error: function (xhr, ajaxOptions, thrownError) {
                console.log(thrownError);
            }
        });

    });
});
