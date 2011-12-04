<?php

/**
  Template Name: Traditional
 */
$font = 'droidserifb';
$logo = '<td rowspan="2" width="150"><img src="%logo%" width="150" /></td>';
$header_width = 'width="442"';

$due_date = '<tr><td align="right" width="40%"><strong>DUE DATE:</strong></td><td align="left" width="60%">%due_date%</td></tr>';
$amount_due = '<tr><td align="right" width="40%"><strong>AMOUNT:</strong></td><td align="left" width="60%"><span style="color: #276822">%amount_due%</span></td></tr>';
$attn = '<tr><td align="right" width="40%"><strong>ATTN:</strong></td><td align="left" width="60%">%attn%</td></tr>';
$bill_to = '<tr><td align="right" width="40%"><strong>BILL TO:</strong></td><td align="left" width="60%"><span style="color: #577695">%bill_to%</span></td></tr>';
$address = '<tr><td align="right" width="40%"><strong>ADDRESS:</strong></td><td align="left" width="60%">%address%</td></tr>';
$telephone = '<tr><td align="right" width="40%"><strong>TELEPHONE:</strong></td><td align="left" width="60%">%telephone%</td></tr>';

$total_tax = '<tr><td style="border-collapse: collapse" colspan="%description_cols%" align="right"><strong>TOTAL <span style="color: #198556">%subtotal%</span></strong></td></tr><tr><td style="border-collapse: collapse" colspan="%description_cols%" align="right"><strong>TAX <span style="color: #198556">%total_tax%</span></strong></td></tr>';
$tax_th = '<th style="border-collapse: collapse; border: 1px solid #9f9f9f" width="100" align="center">TAX</th>';
$tax_td = '<td style="border-collapse: collapse; border: 1px solid #9f9f9f; color: #276822" bgcolor="%bgcolor%" align="center"><b>%line_total_tax%</b></td>';

$name_and_address = '
  <td width="49%" style="border-top: 1px solid #cdcdcd; border-bottom: 1px solid #cdcdcd; ">
    <table border="0" cellspacing="5" cellpadding="0">
    %bill_to%
    %address%
    %telephone%
    </table>
  </td>
';

$terms_n_conditions = '
  <tr>
    <td>
      <table border="0" cellspacing="5" cellpadding="5" width="100%">
        <tr>
          <td style="border-bottom: 1px solid #474747;color: #474747;">TERMS &amp; CONDITIONS</td>
        </tr>
        <tr>
          <td style="color: #474747; font-size: 0.8em;">%terms_n_conditions_text%</td>
        </tr>
      </table>
    </td>
  </tr>
';

$notes = '
  <tr>
    <td>
      <table border="0" cellspacing="5" cellpadding="5" width="100%">
        <tr>
          <td style="border-bottom: 1px solid #474747;color: #474747;">NOTES</td>
        </tr>
        <tr>
          <td style="color: #474747; font-size: 0.8em;">%notes_text%</td>
        </tr>
      </table>
    </td>
  </tr>
';

$content = '
  <tr>
    <td>
      <table border="0" cellspacing="5" cellpadding="0" width="100%">
        <tr>
          <td style="color: #474747; font-size: 0.8em;">%content_text%</td>
        </tr>
      </table>
    </td>
  </tr>  
';

$description_table = '
<table style="border-collapse: collapse; border: 1px solid #9f9f9f" width="600" cellspacing="0" cellpadding="3">
  <tr>
    <th style="border-collapse: collapse; border: 1px solid #9f9f9f" width="%desc_width%">DESCRIPTION</th>
    <th style="border-collapse: collapse; border: 1px solid #9f9f9f" width="100" align="center">QUANTITY</th>
    <th style="border-collapse: collapse; border: 1px solid #9f9f9f" width="100" align="center">AMOUNT</th>
    %tax_th%
  </tr>
  %description_row%
  %total_tax%
  <tr>
    <td style="border-collapse: collapse" colspan="%description_cols%" align="right"><strong>BALANCE: <span style="color: #198556">%grand_total%</span></strong></td>
  </tr>
</table>
';
$description_row_bgcolor[1] = '#e7e7e7';
$description_row_bgcolor[2] = '#ffffff';
$description_row = '
<tr>
  <td style="border-collapse: collapse; border: 1px solid #9f9f9f" bgcolor="%bgcolor%"><b>%name%</b></td>
  <td style="border-collapse: collapse; border: 1px solid #9f9f9f" bgcolor="%bgcolor%" align="center"><b>%quantity%</b></td>
  <td style="border-collapse: collapse; border: 1px solid #9f9f9f; color: #276822" bgcolor="%bgcolor%" align="center"><b>%price%</b></td>
  %tax_td%
</tr>
<tr>
  <td style="border: 1px solid #9f9f9f" colspan="%description_cols%" bgcolor="%bgcolor%">%description%</td>
</tr>
';

$html = '
<table border="0" cellspacing="5" cellpadding="5" width="100%">
  <tr>
    <td>
      <table border="0" cellspacing="3" cellpadding="3" width="600">
        <tr>
            %logo%
            <td style="border-bottom: 1px solid #cdcdcd; font-size: 26px;" align="center" %header_width%><strong>%business_address% %business_phone%<br />%email_address% * %url%</strong></td>
        </tr>
        <tr>
            <td align="center"><strong>%is_quote% <span style="color: #5f80a2">#%id%</span> * %post_date%</strong></td>
        </tr>
      </table>
    </td>
  </tr>
  <tr>
    <td>
      <table border="0" cellspacing="5" cellpadding="5" width="100%">
        <tr>
          <td style="border-top: 1px solid #cdcdcd; border-bottom: 1px solid #cdcdcd;">
            <table border="0" cellspacing="5" cellpadding="0">
              %due_date%
              %amount_due%
              %attn%
            </table>
          </td>
          %name_and_address%
        </tr>
      </table>
    </td>
  </tr>
  %content%
  <tr><td>%description%</td></tr>
  %terms_n_conditions%
  %notes%
</table>
';
?>