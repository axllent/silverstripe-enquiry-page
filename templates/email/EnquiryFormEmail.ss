<html>
<body>
<table cellpadding="5" style="font-family:Arial,helvetica">
<% loop EmailData %>
	<tr>
		<td valign="top"><strong>$Header</strong></td>
		<td valign="top">$Value</td>
	</tr>
<% end_loop %>
</table>
</body>
</html>