{if $errors|@count > 0}
	{include file="$tpl_dir./errors.tpl"}
{else if $mailresended}
    <h2>{l s='Pending registration' mod='emailverify'}</h2>
    <br /><br />
    <h4>
    {l s='Your activation link has been resent to your e-mail address :' mod='emailverify'} <span style="color:#008000">{$mailresended}</span>
    </h4>
{else}
    <h2>{l s='Pending registration' mod='emailverify'}</h2>
    <br />
    <h4>
    {l s='Your account has been successfuly created but need to be activated.' mod='emailverify'}
    <br /><br />
    {l s='An activation link has been sent to your e-mail address.' mod='emailverify'}
    </h4>
{/if}