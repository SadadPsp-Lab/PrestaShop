{*
* 2013 Presta-Shop.ir
*
*
*  @author Presta-Shop.ir - Danoosh Miralayi
*  @copyright  2013 Presta-Shop.ir
*}
{capture name=path}{l s='پرداخت بانک ملی' mod='bankmelli'}{/capture}
{include file="$tpl_dir./breadcrumb.tpl"}


<div class="block-center" id="">
<h2>{l s='پرداخت بانک ملی' mod='bankmelli'}</h2>

{include file="$tpl_dir./errors.tpl"}

{if isset($prepay) && $prepay}
	<br />
	<p>{l s='در حال اتصال به بانک...' mod='bankmelli'}</p>
	<p>{l s='چنانچه به بانک متصل نشدید روی دکمه پرداخت کلیک کنید' mod='bankmelli'}</p>
	<script type="text/javascript">
		setTimeout("document.forms.frmpayment.submit();",10);
	</script>
	<form name="frmpayment" action="{$redirect_link}" method="get">
		<input type="hidden" id="Token" name="Token" value="{$Token}" />
		<input type="submit" class="button" value="{l s='پرداخت' mod='bankmelli'}" />
	</form>
	<p></p>
{/if}
</div>