<!DOCTYPE html>
<head>

  <title>[cjdns]</title>

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

</head>

<body>

<?php echo '<center><pre>' . $obj->Toolshed->ascii_logo() . '</pre></center><hr>'; ?>

<center>
<table class="table table-hover" border="1">

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