<?php

?>

<html>
<head>
  <title>Voting Intentions Poll</title>
  <?php if (IS_PUBLIC) { ?>
  <meta NAME="ROBOTS" CONTENT="INDEX, FOLLOW">
  <?php } else { ?>
  <meta NAME="ROBOTS" CONTENT="NOINDEX, NOFOLLOW">
  <?php } ?>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" type="text/css" href="./css/colours.css" >
  <link rel="stylesheet" type="text/css" href="./css/layout.css" >
  <script src="https://code.jquery.com/jquery-3.1.1.min.js"></script>
  <meta name="description" content="A simple electoral poll to ask for voting intentions">
</head>
<body>
  <div class="maincol">
    <?php // topbar(); ?>
