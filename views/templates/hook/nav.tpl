{if 1 < count($shops)}
<dl id="stores-block-top" class="stores-block pull-left dropdown_wrap">
    {assign var='id_shop' value=$cart->id_shop}
    <dt class="dropdown_tri">
        <div class="dropdown_tri_inner">
            <img src="{$img_lang_dir}{$shops.$id_shop.lang}.jpg" alt="{$shops.$id_shop.iso}" width="16" height="11" class="mar_r4">
            {$shops.$id_shop.name}{if count($shops) > 1}<b></b>{/if}
        </div>
    </dt>
    <dd class="dropdown_list">
        <ul id="first-stores" class="stores-block_ul">
        {foreach from=$shops key=id item=shop}
        {if $id_shop != $id}
            <li>
                <a href="{$shop.link|escape:'html':'UTF-8'}" title="{$shop.name}">
                    <img src="{$img_lang_dir}{$shop.lang}.jpg" alt="{$shop.iso}" width="16" height="11" class="mar_r4">
                    {$shop.name}
                </a>
            </li>
        {/if}
        {/foreach}
        </ul>
    </dd>
</dl>
{/if}
