<!DOCTYPE HTML>
<html>
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
		<meta name="viewport" content="width=device-width, initial-scale=1">
	</head>
<body>

<!-- <form name="test" method="get" action="php/getvalues.php"> -->
<p><b>Введите id (только 1):</b><br>
   id ступени: <br><input id="stage_id"     type="text" value="1"><br>
   кол. ступеней: <br><input id="stage"        type="text" value="5"><br>
   кол. значений в табл: <br><input id="valQuantity"  type="text" value="17"><br>
</p>
<p><input id="send" type="submit" value="Отправить"> </p>
</body>
<script src="libs/jquery-3.4.1.js"></script>
<script type="text/javascript">
	$('#send').click(function() {
            $.ajax({
                url: "php/getvalues.php",
                type: "POST",
                data: ({
                    id: $('#stage_id').val(),
                    stages: $('#stage').val(),
                    valQuantity: $('#valQuantity').val(),
                }),
                dataType: "json",
                success: function(data){
                    console.log(data);
                },
                error: function (jqXHR, exception) {
                    console.error('ajax error, jqXHR.status: ' + jqXHR.status);
                    console.error('responseText: \n' + jqXHR.responseText);
                }
            });
        });
</script>
</html>