// Este código automatiza a finalização do pedido junto ao painel da loja e está localizado dentro da página de retorno, que será ocultada na barra de menus.
// É necessário o plugin Allow PHP in Posts and Pages - versão 3.0.4 - http://www.hitreach.co.uk/wordpress-plugins/allow-php-in-posts-and-pages/
// A partir da linha abaixo, o texto completo deverá ser colado no post da página de retorno. 

Seu pedido foi finalizado com sucesso.

[php]

if (($_POST['status']) == "OK") {

$order = new WC_Order ($_POST['pedidoID']);

if ( ($order->id) == ($_POST['pedidoID'])) {

$order -> payment_complete();

$order -> update_status('completed');

echo '<p>Pedido ID: '.$_POST['pedidoID'].'</p>';

echo '<p>Valor do Pedido: R$ '.$_POST['valor_pedido'].'</p>';

}

}

[/php]