{if $errors|@count > 0}
	{include file="$tpl_dir./errors.tpl"}
{else}
	<h2>{l s='Registration completed' mod='emailverify'}</h2>
        <br />
        <h4 style="color:#008000">
	{l s='Your account has been successfuly activated.' mod='emailverify'}<br />
        <br /><br />
	{l s='Thank you for creating your account. You can now place orders on our shop.' mod='emailverify'}
        </h4>
        <br /><br />
        <div style="text-align:right"><a href="{$link->getPageLink('my-account.php')}" class="button_large">{l s='Your account' mod='emailverify'}</a></div>
{/if}