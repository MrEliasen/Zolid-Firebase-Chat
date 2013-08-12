<?php
    require_once('core/initchat.php');
    $Chat->executeRequest();

?><!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <title>Zolid Firebase Chat</title>

        <!-- Bootstrap CSS -->
        <link href="assets/css/bootstrap.min.css" rel="stylesheet" type="text/css">
        <link href="assets/css/bootstrap-glyphicons.css" rel="stylesheet" type="text/css">

        <!-- Chat CSS -->
        <link href="assets/css/chat.css" rel="stylesheet" type="text/css">
        
        <!--[if lt IE 9]>
        <script src="http://html5shim.googlecode.com/svn/trunk/html5.js"></script>
        <![endif]-->
    </head>
    <body>
        <div class="main">
            <div class="container">
                <div class="row">

                    <div id="onlineusers" class="col-lg-2">
                        <h5><span class="oucount badge badge-info">0</span> Online</h5>
                        <div class="ounames"></div>
                    </div>

                    <div class="col-lg-10">
                        <h3>Live Chat <small><a href="?admin=true">Become Admin</a></small></h3>
                        <!-- the actual chat -->
                        <ul class="nav nav-tabs" id="chatTab">
                            <li class="active"><a href="#general" data-toggle="tab">General Chat</a></li>
                            <li><a href="#private" data-toggle="tab">Private Messages</a></li>
                        </ul>
                        
                        <div class="tab-content">
                            <div class="tab-pane active" id="general">
                                <div id="chat_conversation">
                                    <div id="chatloader">
                                        <img src="assets/images/loading.gif" alt="Kitty Amazing"> Loading Chat..
                                        <div class="clearfix"></div>
                                        This might take a few seconds
                                    </div>
                                </div>
                                <div class="form-inline">
                                    <input autocomplete="off" id="message" name="message" type="text" class="col-lg-12 form-control" placeholder="Type your message, and hit enter!">
                                    <div class="clearfix"></div>
                                    <span class="chatcountdown">1000 characters remaining</span>
                                </div>
                            </div>
                            <div class="tab-pane" id="private">
                                <div id="pm_conversation"></div>
                                <div class="row">
                                    <div class="col-lg-3">
                                        <select class="form-control" id="userselect"></select>
                                    </div>
                                    <div class="col-lg-9">
                                        <input autocomplete="off" id="pmmessage" name="message" type="text" class="form-control" placeholder="Type your message, and hit enter!">
                                        <span class="chatcountdown">1000 characters remaining</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>

        <!-- jQuery -->
        <script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jquery/1.9.1/jquery.min.js"></script>
        <script>
            // Include jQuery from local source is it was not included from cdn.
            window.jQuery || document.write('<script type="text/javascript" src="assets/js/jquery-1.9.1.min.js"><\/script>');
        </script>
        
        <!-- Include firebase -->
        <script type="text/javascript" src="https://cdn.firebase.com/v0/firebase.js"></script>
        <!-- Bootstrap for the visuals -->
        <script type="text/javascript" src="assets/js/bootstrap.min.js"></script>
        <!-- And now, the chat itself -->
        <script type="text/javascript" src="assets/js/chat.js"></script>
        
    </body>
</html>