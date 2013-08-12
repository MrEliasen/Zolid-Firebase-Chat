var firebase_url = 'zolidchat',
    userInfo;

$(document).ready(function () {
    "use strict";

    // Get the information about the user we need.
    // This is just a rough way of doing it, merely to illustrate the purpose
    $.ajax({
        url: 'index.php',
        type: 'POST',
        dataType: "json",
        data: "request=getuserdata",
        success: function(reply){
            userInfo = reply;
            InitChat();
        }
    });
});


/*
 *  From the Zolid Framework
 */
/* 
 * Slides up the notification, to hide it.
 * * * * * * * * * * * * * * * * * * * * */
var notificationInt = null;
function hideNotification() {
    clearInterval(notificationInt);
    $("#alertMessage").animate({
        top: -$("#alertMessage").height()
    }, 500);
}

/*
 *  From the Zolid Framework
 */
/* 
 * display a sldie now notification, useful for ajax responses.
 * a = title
 * b = body
 * c = notification type: error, success, warning, info.
 * sticky = true/false - keep the notification at the top until the user closes it.
 * * * * * * * * * * * * * * * * * * * * */
function showNotification(a, b, c, sticky) {
    hideNotification();

    if (sticky === 'undefined') {
        sticky = false;
    }

    if (b === 'undefined' || b == '' ) {
        b = '';
    } else {
        b = '<h4 class="alert-heading">' + b + '</h4>';
    }

    switch (c) {
    case "error":
        c = "alert-error";
        break;
    case "info":
        c = "alert-info";
        break;
    case "success":
        c = "alert-success";
        break;
    default:
        c = "";
        break;
    }

    var position = $("#alertMessage").position();
    $("#alertMessage").html('<div class="alert ' + c + '"><button type="button" class="close" data-dismiss="alert">&times;</button>' + b + a + "</div>").stop(true, true).animate({
        top: 0
    }, 500, function () {
        if (!sticky) {
            notificationInt = setInterval(hideNotification, 5000);
        }
    });
}

function replaceEmoticons(text) {
    var emoticons = {
        ':D' :      '<img src="assets/images/emoticons/big_grin.png" alt="">',
        'xD' :      '<img src="assets/images/emoticons/big_grin_squint.png" alt="">',
        '[poker]' : '<img src="assets/images/emoticons/bored.png" alt="">',
        ']:)' :     '<img src="assets/images/emoticons/evil.png" alt="">',
        ':(' :      '<img src="assets/images/emoticons/frown.png" alt="">',
        '[heart]' : '<img src="assets/images/emoticons/heart.png" alt="">',
        ':x' :      '<img src="assets/images/emoticons/kiss.png" alt="">',
        ':X' :      '<img src="assets/images/emoticons/kiss.png" alt="">',
        '[mad]' :   '<img src="assets/images/emoticons/mad.png" alt="">',
        ':O' :      '<img src="assets/images/emoticons/oh_rly.png" alt="">',
        ':o' :      '<img src="assets/images/emoticons/oh_rly.png" alt="">',
        ':)' :      '<img src="assets/images/emoticons/smile.png" alt="">',
        ':S' :      '<img src="assets/images/emoticons/sour.png" alt="">',
        ':s' :      '<img src="assets/images/emoticons/sour.png" alt="">',
        '8)' :      '<img src="assets/images/emoticons/sunglasses.png" alt="">',
        ':P' :      '<img src="assets/images/emoticons/tongue.png" alt="">',
        ';)' :      '<img src="assets/images/emoticons/wink.png" alt="">',
        ';P' :      '<img src="assets/images/emoticons/wink_tongue.png" alt="">',
    }, patterns = [],
    metachars = /[[\]{}()*+?.\\|^$\-,&#\s]/g;

    // build a regex pattern for each defined property
    for (var i in emoticons) {
        if (emoticons.hasOwnProperty(i)){ // escape metacharacters
            patterns.push('(' + i.replace(metachars, "\\$&") + ')');
        }
    }

    // build the regular expression and replace
    return text.replace(new RegExp(patterns.join('|'),'g'), function (match) {
        return typeof emoticons[match] != 'undefined' ? emoticons[match] : match;
     });
}

function InitChat() {
    // Chat general variables
    var dataRef = new Firebase('https://' + firebase_url + '.firebaseio.com/chat/'),
        sending = false, firstload = true, message, remove, update, modid, modtools = '';
    
    // Online User counter
    var listRef = new Firebase('https://' + firebase_url + '.firebaseio.com/users/'),
        userRef = listRef.push();

    // Add ourselves to presence list when online.
    var presenceRef = new Firebase('https://' + firebase_url + '.firebaseio.com/.info/connected');

    // authenticate the user, maybe..
    dataRef.auth(userInfo.fbtoken, function(error, token) {
        if( error ) {
            // failed
        } else {
            userRef.set({user : token.auth.user, status : true, label : token.auth.label});
            // Remove ourselves when we disconnect.
            userRef.onDisconnect().remove();
            pmRef.onDisconnect().remove();
        }
    });
    
    // Number of online users is the number of objects in the presence list.
    listRef.on("value", function(snap) {
        $('#onlineusers .oucount').html( snap.numChildren() );
        
        if( $('#chat_conversation').length ){
            $('#onlineusers .ounames').html('');
            $('#userselect').html('');
            $.each( snap.val(), function( index, value ) {
                if( value.user !== userInfo.username ){
                    $('#userselect').append('<option value="' + value.user + '">' + value.user + '</option>');
                }
                $('#onlineusers .ounames').append(' <span id="usr_' + value.user + '" class="label ' + value.label + '">' + value.user + '</span>');
            });
        }
    });
    // End of user counter
    
    $('#chatTab a').click(function (e) {
        e.preventDefault();
        $(this).tab('show');
        
        if($(this).attr('href') === '#private'){
           $(this).find('.glyphicon-comment').remove();
        }
    });
    
    // Private messages
    var pmRef = new Firebase('https://' + firebase_url + '.firebaseio.com/private/' + userInfo.username),
        sendto, pmupdate, pmmessage, pmremove;
    
    // Add a callback that is triggered for each chat message.
    dataRef.limit(10).on('child_added', function (snapshot) {
        if( firstload ){
            firstload = false;
            $('#chat_conversation').html('');
        }
        
        message = snapshot.val();

        if( userInfo.isadmin )
        {
            // No, you still need admin to actually use these functions, its just cosmetics.. ;) you dodgy .. person!
            modtools = '<i class="glyphicon glyphicon-remove deletemsg" data-toggle="tooltip" title="Delete Message" data-msgid="' + message.msgid + '"></i>' +
                       '<i class="glyphicon glyphicon-edit editmsg" data-toggle="tooltip" title="Edit Message" data-msgid="' + message.msgid + '"></i> ';
        }
        $('#chat_conversation').append('<div id="' + message.msgid + '" class="well well-small">' +
                                       '<i class="glyphicon glyphicon-time" data-toggle="tooltip" title="Sent: ' + message.time + '"></i>' +
                                       modtools +
                                       '<span class="label ' + message.label + '">' + message.username + '</span>: ' + message.edited + ' <span class="msgcontent">' + replaceEmoticons(message.string) + '</span></div>');
        
        $('i[data-toggle="tooltip"]').tooltip({placement: 'right'});
        $('#chat_conversation')[0].scrollTop = $('#chat_conversation')[0].scrollHeight;
    });
    dataRef.on('child_changed', function(snapshot) {
        update = snapshot.val();
        $('#' + update.msgid).find('.msgcontent').html(replaceEmoticons(update.string));
        $('#' + update.msgid).find('.glyphicon-warning-sign').remove();
        $('#' + update.msgid).find('.msgcontent').before(update.edited + ' ');
        $('i[data-toggle="tooltip"]').tooltip({placement: 'right'});
    });
    dataRef.on('child_removed', function(snapshot) {
        remove = snapshot.val();
        $('#' + remove.msgid).fadeOut();
    });
    dataRef.on('value', function(snapshot) {
        if( firstload ){
            firstload = false;
            $('#chat_conversation').html('');
        }
    });
    
    /* * * * * * * * * * * * * * * * *
             Private Messages
    * * * * * * * * * * * * * * * * */
    pmRef.on('child_added', function (snapshot) {
        //Show notification
        if( $('#chatTab li:first-child').hasClass('active') && !$('#chatTab li:last-child .glyphicon-comment').length ){
            $('#chatTab li:last-child a').prepend('<i class="glyphicon glyphicon-comment"></i>');
        }
        
        pmmessage = snapshot.val();
        $('#pm_conversation').append('<div id="' + pmmessage.msgid + '" class="well well-small">' +
                                       '<i class="glyphicon glyphicon-time" data-toggle="tooltip" title="Sent: ' + pmmessage.time + '"></i>' +
                                        ( pmmessage.username !== userInfo.username ? 'From: <span class="label label-warning sendreply">' + pmmessage.username + '</span>' : 'You said to ' + pmmessage.to ) +
                                       ': ' + pmmessage.edited + ' <span class="msgcontent">' + replaceEmoticons(pmmessage.string) + '</span></div>');
        
        $('i[data-toggle="tooltip"]').tooltip({placement: 'right'});
        $('#pm_conversation')[0].scrollTop = $('#pm_conversation')[0].scrollHeight;
    });
    pmRef.on('child_changed', function(snapshot) {
        pmupdate = snapshot.val();
        $('#' + pmupdate.msgid).find('.msgcontent').html(replaceEmoticons(pmupdate.string));
        $('#' + pmupdate.msgid).find('.glyphicon-warning-sign').remove();
        $('#' + pmupdate.msgid).find('.msgcontent').before(pmupdate.edited + ' ');
        $('i[data-toggle="tooltip"]').tooltip({placement: 'right'});
    });
    pmRef.on('child_removed', function(snapshot) {
        pmremove = snapshot.val();
        $('#' + pmremove.msgid).fadeOut();
    });
    /* * * * * * * * * * * * * * * * *
        End of Private Messages
    * * * * * * * * * * * * * * * * */
    
    $('#message, #pmmessage').keyup(function (e) {
        $(this).parent().find('.chatcountdown').text( (1000 - $(this).val().length) + ' characters remaining.');
    });
    
    $('#message, #pmmessage').keypress(function (e) {
        if (e.keyCode == 13) {
            if(sending) {
                return false;
            }
            sending = true;
            message = encodeURIComponent( $(this).val() );
            $(this).val('');
            
            sendto = '';
            if( $(this).attr('id') == 'pmmessage' )
            {
                sendto = '&private=' + $('#userselect').val();
                if( $('#userselect').val() === null )
                {
                    sending = false;
                    return false;
                }
            }
            
            $.ajax({
                url: 'index.php',
                type: 'POST',
                data: 'message=' + message + sendto + '&request=newchatmsg',
                dataType: "json",
                success: function (reply) {
                    if( !reply.status )
                    {
                        showNotification(reply.error, 'Error');
                    }
                    sending = false;
                    return true;
                }
            });
        }
    });
    
    $(document).on("click", ".deletemsg", function(e){
        modid = $(this).attr('data-msgid');
        $.ajax({
            url: 'index.php',
            type: 'POST',
            data: 'msgid=' + modid + '&request=deletechatmsg',
            dataType: "json",
            success: function (reply) {
                if( !reply.status )
                {
                    showNotification(reply.error, 'Error');
                }
            }
        });
    });
    
    $(document).on("click", ".sendreply", function(e){
        $('#userselect').val( $(this).html() );
    });

    $(document).on("click", ".editmsg", function(e){
        var editedmsg = prompt('Edit Message', $(this).parent().find('.msgcontent').html());
        if( editedmsg == null){
            return false;
        }
        modid = $(this).attr('data-msgid');
        $.ajax({
            url: 'index.php',
            type: 'POST',
            data: 'msgid=' + modid + '&message=' + editedmsg + '&request=editchatmsg',
            dataType: "json",
            success: function (reply) {
                if( !reply.status )
                {
                    showNotification(reply.error, 'Error');
                }
            }
        });
    });
}