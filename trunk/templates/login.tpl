{include file="header.tpl"}

{if !$auth.isGuest}
    Login:
    <form method="post">
        User: <input type="text" size="10" name="user" value='{$smarty.post.user|escape:"htmlall"}' />
        Password: <input type="password" size="10" name="pass" />
    </form>
{/if}

{include file="footer.tpl"}