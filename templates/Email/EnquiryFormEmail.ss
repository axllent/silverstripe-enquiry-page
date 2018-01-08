<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
  <body>
    <table cellpadding="5" style="font-family:Arial,helvetica">
      <% loop $EmailData %>
        <tr>
          <% if $Type == 'Header' %>
            <td valign="top" colspan="2" style="padding-top:10px; font-size:120%">
              <u><b>$Header</b></u>
            </td>
          <% else %>
            <td valign="top"><b>$Header</b></td>
            <td valign="top">
              <% if $Value.count %><%-- This is an array --%>
                <ul>
                  <% loop $Value %><li>$Item</li><% end_loop %>
                </ul>
              <% else %>
                  $Value.XML
              <% end_if %>
            </td>
          <% end_if %>
        </tr>
      <% end_loop %>
    </table>
  </body>
</html>
