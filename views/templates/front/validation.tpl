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
	{if isset($access) && $access=='denied'}
		<br/>
	{else}
		<br/>

		{if isset($paid) && $paid && !empty($sale_order_id) && !empty($sale_refference_id)}
			<div style="background-color: green; color: #fff;">
				<p><strong>{l s='سفارش شما با موفقیت ثبت شد' mod='bankmelli'}</strong></p>
				<p class="required">
					{l s='اطلاعات پرداخت در زیر آمده است. چنانچه مایلید می توانید به جهت پیگیری آن ها را یادداشت نمایید.' mod='bankmelli'}
				</p>
				<p>
					{l s='شناسه سفارش در فروشگاه:' mod='bankmelli'} {$order_reference}<br/>
					{l s='شناسه پرداخت:' mod='bankmelli'} {$sale_order_id}<br/>
					{l s='کد مرجع پرداخت:' mod='bankmelli'} {$sale_refference_id}
				</p>
			</div>
		{/if}

		{* start details *}
		<p>
			<strong>{l s='جزئیات فرآیند پرداخت:' mod='bankmelli'}</strong>
			{if isset($errors) && count($errors)}
				<ul>
					<li>
						{l s='خطایی روی داده است. برای اطمینان با خدمات مشتریان تماس بگیرید.' mod='bankmelli'}
					</li>
					{foreach from=$errors item=error}
					    <li>{$error}</li>
					{/foreach}
				</ul>
			{/if}
		</p>
		{* end details*}
		<br/>
		<p class="bold">
			<a href="{$link->getPageLink('history', true)}">» {l s='نمایش سفارش های من' mod='bankmelli'}</a>
		</p>
		<p>
			{l s='در صورتی که هرگونه سوال، نظر یا مشکلی دارید با بخش' mod='bankmelli'}
			<a href="{$link->getPageLink('contact', true)}"><strong>{l s='تیم پشتیبانی مشتریان تماس بگیرید' mod='bankmelli'}</strong></a>
		</p>
	{/if}
	<p style="float:left; font-size:9px;color:#c4c4c4">bankmelli ver
		<a href="http://presta-shop.ir/" style="color:#c4c4c4">{$ver}</a>
	</p>
</div>