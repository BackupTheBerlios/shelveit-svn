{include file="header.tpl"}

{* Print the form, preserving submitted values if they exist *}
<form method="get">
   Search for:
   <select name="type">
		{html_options values=$queryTypes
					 output=$queryTypes
					 selected=$smarty.get.type}
   </select>
   <input type="text" size="30" name="term" value='{$smarty.get.term|escape:"htmlall"}' />
   <input type="hidden" name="step" value='{$step}' />
   <input type="submit" value="Search" />
</form>

{if isset( $results ) }
	Your search returned {$hits} hits<br />
{/if}

{* if there's a correction to the query text to display *}
{if isset( $correctedTerm ) }
	Did you mean <a href="{$correctedURL}">{$correctedTerm}</a>?<br />
{/if}

{* Print results, if any *}
{if count( $results ) > 0 }
	<table border="1">
		<tr>
                        <td></td>
			<td>Title
                                <a href="{$sortURLs.Title.asc}">^</a>
                                <a href="{$sortURLs.Title.desc}">v</a></td>
			<td>Author
                                <a href="{$sortURLs.Author.asc}">^</a>
                                <a href="{$sortURLs.Author.desc}">v</a></td>
			<td>Year
                                <a href="{$sortURLs.Year.asc}">^</a>
                                <a href="{$sortURLs.Year.desc}">v</a></td>
		</tr>
                {foreach from=$results item=book}
		<tr>
			<td><a href="{$book.addURL}">+</a></td>
			<td><a href="{$book.detailURL}">{$book.title}</a></td>
			<td>{$book.author}</td>
			<td>{$book.year}</td>
		</tr>
                {/foreach}
	</table>
{/if}

{* Print links to previous and next pages, if needed *}
{if ( isset( $prevURL ) || isset( $nextURL ) ) }
	<table>
	<tr>
	<td>
	{if $prevURL}
		<a href="{$prevURL}">Previous</a>
	{/if}
	</td>
	<td>
	{if $nextURL}
		<a href="{$nextURL}">Next</a>
	{/if}
	</td>
	</tr>
	</table>
{/if}

{include file="footer.tpl"}