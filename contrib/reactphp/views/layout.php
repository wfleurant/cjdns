<!DOCTYPE html>
<html lang="en">
<head>

  <meta charset="UTF-8">
  <title>[cjdns]</title>

  <link rel="stylesheet" href="bootstrap.min.css">

  <style>

    body {
      margin: 14px;
      font: 8px Helvetica, sans-serif;
      font-weight: 100;
      vertical-align: all;
    }

    th {
      font: 11px Helvetica, sans-serif;
      background-color: green;
      color: white;
    }

    tr:hover {
        background-color: #f5f5f5
    }

    tr:nth-child(even) {
        background-color: #f2f2f2
    }

    td {
      font: 11px Helvetica, sans-serif;
      vertical-align: left;
    }

    pre {
      background-color: black;
      color: green;
      display: block;
      font-family: monospace;
      white-space: pre;
      margin: 2em 0;
    }

  </style>


  <link rel="stylesheet" href="bootstrap-table.min.css">

  <script src="jquery-2.0.2.min.js"></script>
  <script src="bootstrap.min.js"></script>
  <script src="bootstrap-table.min.js"></script>

  <script type="text/javascript">

    $(window).resize(function() {
        $('#peerstats-table').bootstrapTable('resetView');
    });

    jQuery(document).ready(function($) {

      setInterval(function() {

        jQuery.ajax({
          url: '/nodes',
          type: 'GET',
          dataType: 'json',
          data: {q: 'nodes'},
          success: function(xhr, textStatus)
          {

            $('#peerstats-table').bootstrapTable('destroy');
            $('#peerstats-table').bootstrapTable({
                data: xhr.peers
            });

          },
          error: function(xhr, textStatus, errorThrown)
          {
            console.log(errorThrown);
            console.log(xhr);
          }
        });

      }, 1 * 1000);

    });


  </script>

</head>

<body>

<?php echo '<center><pre>' . $obj->Toolshed->ascii_logo() . '</pre></center><hr>'; ?>

<center>

<!-- Todo:
    utilize front-end functions on the API query to return humnan-
    readable and parse_peerstats() rows Seen on the 1-time page load

    Connectivity
    cjdns Address
    Total RX
    Total TX
    RX Speed
    TX Speed
    Last Pkt
    Public Key
-->

<table id="peerstats-table"
    data-pagination="false"
    data-search="false"
    data-classes="table"
    class="table table-hover table-condensed"
    data-show-columns="false"
    data-show-refresh="false"
    data-show-toggle="false"
    data-striped="false">

    <thead class="smallfont">
        <tr>
            <th data-field="state"     data-sortable="true" class="col-md-0">
                Connectivity
            </th>
            <!-- <th data-field="addr"      data-sortable="true" class="col-md-0"> -->
                <!-- cjdns Address -->
            <!-- </th> -->
            <th data-field="bytesIn"   data-sortable="true" class="col-md-0">
                Total RX
            </th>
            <th data-field="bytesOut"  data-sortable="true" class="col-md-0">
                Total TX
            </th>
            <th data-field="recvKbps"  data-sortable="true" class="col-md-0">
                RX Speed
            </th>
            <th data-field="sendKbps"  data-sortable="true" class="col-md-0">
                TX Speed
            </th>
            <th data-field="last"      data-sortable="true" class="col-md-0">
                Last Pkt
            </th>
            <th data-field="publicKey" data-sortable="true" class="col-md-0">
                Public Key
            </th>

        </tr>
    </thead>
</table>

<hr>


<table border="1">

  <thead>
    <tr>
        <th>Connectivity</th>
        <th>cjdns Address</th>
        <th>Total RX</th>
        <th>Total TX</th>
        <th>RX Speed</th>
        <th>TX Speed</th>
        <th>Last Pkt</th>
        <th>Public Key</th>
    </tr>
  </thead>

  <tbody>
  <?php
  foreach ($obj->peerstats['peers'] as $key => $value) {
    echo "<tr>";

        echo "<td>" . $value['state']       . "</td>";

        echo "<td>" . $obj->Toolshed
                          ->parse_peerstats($value['addr'])['ipv6'] . "</td>";

        echo "<td>" . \ByteUnits\bytes($value['bytesIn'])->format(null, '&nbsp;')  . "</td>";

        echo "<td>" . \ByteUnits\bytes($value['bytesOut'])->format(null, '&nbsp;') . "</td>";

        echo "<td>" . number_format($value['recvKbps']/1000, 3) . "&nbsp;mbps</td>";

        echo "<td>" . number_format($value['sendKbps']/1000, 3) . "&nbsp;mbps</td>";

        echo "<td>" . Carbon\Carbon::now('UTC')->diffForHumans(
                        Carbon\Carbon::createFromTimeStampUTC(
                            round($value['last'] / 1000)), TRUE) . "</td>";

        echo "<td>" . $value['publicKey']   . "</td>";

    echo "</tr>";

  } ?>
  </tbody>

</table>
</center>

</body>