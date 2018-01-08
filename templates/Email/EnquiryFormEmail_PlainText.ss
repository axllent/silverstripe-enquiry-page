<% loop $EmailData %>
<% if $Type == 'Header' %>
$Header.RAW
=======<% else %>
$Header.RAW
-------
<% if $Value.count %><% loop $Value %>- $Item.RAW<% if $Last %><% else %>
<% end_if %><% end_loop %><% else %>$Value.RAW<% end_if %>
<% end_if %><% end_loop %>
