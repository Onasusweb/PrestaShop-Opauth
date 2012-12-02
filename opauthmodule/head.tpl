	


{if !$cookie->isLogged()}
<p>Log in with:</p>
	<ul>
		<li class='liconnect'><a href="{$url}/modules/opauthmodule/facebook"><img src="{$url}/modules/opauthmodule/img/icon/fac.png"/></a></li>
		<li class='liconnect'><a href="{$url}/modules/opauthmodule/google"><img src="{$url}/modules/opauthmodule/img/icon/go.png"/></a></li>
		<li class='liconnect'><a href="{$url}/modules/opauthmodule/twitter"><img src="{$url}/modules/opauthmodule/img/icon/twi.png"/></a></li>
	</ul>

{/if}
