<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
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
