{include file="admin/header.tpl"}

<div id="mainerror">
    {include file="admin/mainerror.tpl"}
</div>

<div id="mainpanel" align="center">
	<table cellpadding="0" cellspacing="0" border="0" class="tabelle">
		<tr>
			<td>
				{t}This feature takes a lot time and need access to remote files. Not every server satisfy this requirements.{/t}
			</td>
		</tr>
	</table>

	<br />

	<table cellpadding="0" cellspacing="0" border="1" style="text-align:center; width:480px;" class="box">
		<tr>
			<td width="40%" />
			<td width="20%">{t}Total{/t}</td>
			<td width="20%">{t}Outdated{/t}</td>
			<td width="20%">{t}New{/t}</td>
		</tr>
		<tr>
			<td>IMDB</td>
			<td><a class="button" href="updateimdb.php?action=update&amp;{$csrf_token_url}&amp;what=imdb">{$imdb_all}</a></td>
			<td><a class="button" href="updateimdb.php?action=update&amp;{$csrf_token_url}&amp;what=imdb_old">{$imdb_old}</a></td>
			<td><a class="button" href="updateimdb.php?action=update&amp;{$csrf_token_url}&amp;what=imdb_new">{$imdb_new}</a></td>
		</tr>
		<tr>
			<td>OFDB</td>
			<td><a class="button" href="updateimdb.php?action=update&amp;{$csrf_token_url}&amp;what=ofdb">{$ofdb_all}</a></td>
			<td><a class="button" href="updateimdb.php?action=update&amp;{$csrf_token_url}&amp;what=ofdb_old">{$ofdb_old}</a></td>
			<td><a class="button" href="updateimdb.php?action=update&amp;{$csrf_token_url}&amp;what=ofdb_new">{$ofdb_new}</a></td>
		</tr>
		<tr>
			<td>RottenTomatoes</td>
			<td><a class="button" href="updateimdb.php?action=update&amp;{$csrf_token_url}&amp;what=rotten">{$rotten_all}</a></td>
			<td><a class="button" href="updateimdb.php?action=update&amp;{$csrf_token_url}&amp;what=rotten_old">{$rotten_old}</a></td>
			<td><a class="button" href="updateimdb.php?action=update&amp;{$csrf_token_url}&amp;what=rotten_new">{$rotten_new}</a></td>
		</tr>
	</table>

	<br />

	<table cellpadding="0" cellspacing="0" border="0" align="center" style="text-align: center" class="box">
	<tr>
		<td>
		<table cellpadding="0" cellspacing="0" border="0" style="width: 450px">
			<tr>
			<td>
				<a class="button" href="updateimdb.php?action=imdbtop250&amp;{$csrf_token_url}">{t}Click here to create IMDB/OFDB/RottenTomatoes Tags.{/t}</a>
			</td>
			</tr>
		</table>
		</td>
	</tr>
	</table>

</div>

{include file="admin/footer.tpl"}
