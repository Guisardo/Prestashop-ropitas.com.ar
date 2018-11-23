$(document).ready(function() {
	$(window).on('beforeunload', function() {
		var $page = $('#page');
		var $loader = $('#header_logo a').css({
		    'position': 'fixed',
		    'z-index': 9999,
		    'top': '50%',
		    'left': '50%',
		    'margin-top': '-80px',
		    'margin-left': '-80px'
		}).addClass('ld ld-slide-btt x4');
		$page.css({'opacity': 0.3});
		$page.after($loader);
	});

	var tpl_protectedBuy = 
'<li>' +
'	<a href="https://www.mercadopago.com.ar/ayuda/Compra-protegida_601" title="¿Sabías que tu compra está protegida?" target="_blank">' +
'		¿Sabías que tu compra está protegida?' +
'	</a>' +
'</li>';
	$('#block_various_links_footer ul').append(tpl_protectedBuy);

	var tpl_secure =
'<li>' +
'	<a href="http://qr.afip.gob.ar/?qr=z7JppuZzUTVA8PGEhnazew,," target="_F960AFIPInfo">' +
'	    <img src="/modules/ps_wsfe/img/dataweb.jpg" border="0" style="width: 45px;">' +
'	</a>' +
'	<a href="https://ssl.comodo.com" target="_blank">' +
'	    <img src="https://ssl.comodo.com/images/comodo_secure_seal_76x26_transp.png" alt="SSL" width="76" height="26" style="border: 0px;">' +
'	</a>' +
'	<a href="https://www.mercadopago.com.ar/ayuda/dinero-seguridad-compras_283" target="_blank">' +
'		<div style="display: inline-block; background-color: white; width: 133px; height: 52px;"><div style="background-position: 0 -128px; height: 60px; width: 130px; background-image: url(https://http2.mlstatic.com/secure/salesforce-resources/resourcesMpPortal//images/security-standards.1.1__vca9022531ea.png); background-repeat: no-repeat; display: inline-block; margin: 2px; overflow: hidden; text-indent: 100%; vertical-align: middle; white-space: nowrap; ">PCI</div></div>' +
'	</a>' +
'</li>';
	$('#block_contact_infos ul').append(tpl_secure);

	$('.ajax_add_to_cart_button').remove();
});
