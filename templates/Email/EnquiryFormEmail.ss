<!DOCTYPE html>
<html>
  <body>
	<table cellpadding="5" style="font-family:Arial,helvetica">
	  <% loop $EmailData %>
		<tr>
			<td valign="top"><b>$Header</b></td>
			<td valign="top">
			  <% if $Value.count %><%-- This is an array --%>
				<ul style="padding:0; margin:0; list-style-position:inside;">
				  <% loop $Value %><li style="margin-left:0">$Item</li><% end_loop %>
				</ul>
			  <% else %>
				  $Value
			  <% end_if %>
			</td>
		</tr>
	  <% end_loop %>
	</table>
  </body>
</html>
