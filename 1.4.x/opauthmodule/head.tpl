{if !$cookie->isLogged()}
<p>Log in with:</p>
	<ul>
		<li class='liconnect'><a href="{$content_dir}modules/opauthmodule/facebook" class="zocial facebook">Sign in with Facebook</a></li>
		<li class='liconnect'><a href="{$content_dir}modules/opauthmodule/google" class="zocial googleplus">Sign in with Google+</a></li>
		<li class='liconnect'><a href="{$content_dir}modules/opauthmodule/twitter" class="zocial twitter">Sign in with Twitter</a></li>
		<li class='liconnect'><a href="{$content_dir}modules/opauthmodule/linkedin" class="zocial linkedin">Sign in with LinkedIn</a></li>
		<li class='liconnect'><a href="{$content_dir}modules/opauthmodule/paypal" class="zocial paypal">Sign in with PayPal Access</a></li>
	</ul>
{/if}