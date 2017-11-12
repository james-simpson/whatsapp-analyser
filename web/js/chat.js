var senderToShowAsMe;

$(document).ready(function(){

    // Initializes and creates emoji set from sprite sheet
    window.emojiPicker = new EmojiPicker({
      emojiable_selector: '[data-emojiable=true]',
      assetsPath: 'img/',
      popupButtonClasses: 'fa fa-smile-o',
      norealTime: false
    });

    window.emojiPicker.discover();

    $('#importFile').change(function(){
        if ($(this).val() !== "") {
            fileImport.uploadChatFile();
        }
    });

    $('.emoji-wysiwyg-editor').keypress(function(e) {
        if (e.which == 13) {
            e.preventDefault();
            $('#frmSearchMessages').submit();
        }
    });

    $('#frmSearchMessages').submit(function(e){
        e.preventDefault();

        $('#btnSearch').blur();
        $('#btnSearchText').text("Searching messages...");
        $('#btnSearch').prepend("<div class='loader'></div>");
        $('#btnSearch').addClass("disabled");
        $('#results').empty();
        $(".sidebar-right").animate({width:'0'}, 350);
        $('#chatPreview').empty();

        $('#chatPreview').append("<div class='loadingText lead'>Searching messages...</div>");

        var searchTerm = $('#searchTerm').val();
        $.post("searchMessages", {chatId: chat.id, searchTerm: searchTerm}, function(response) {

            $('#chatPreview').find('.loadingText').remove();

            var messages = JSON.parse(response.messages);
            displaySearchCounts(response.counts, searchTerm)

            if (messages.length === 0) {
                $('#chatPreview').append("<div class='no-messages lead'>No matching messages</div>");
            }

            var searchTermContainsEmoji = false;
            for (var key in Config.Emoji) {
                if (searchTerm.includes(Config.Emoji[key][0])) {
                    searchTermContainsEmoji = true;
                }
            }

            var previousMsg = null;
            for (var i = 0; i < messages.length; i++) {
                msg = messages[i];
                if (i > 0) {
                    previousMsg = messages[i - 1];
                }

                var msgElement = formatMsgElement(msg, previousMsg, searchTerm, searchTermContainsEmoji) ;

                $('#chatPreview').append(msgElement);
            };

            $('#btnSearchText').text("Search");
            $('#btnSearch > div').remove(".loader");
            $('#btnSearch').removeClass("disabled");
        });
    });

    $("#btnModalOk").click(function() {
        // set the sender whose messages will be shown
        // on the right hand side
        senderToShowAsMe = $('#selChatMember').val();
    });
});

function displaySearchCounts(counts, searchTerm) {
    $(".sidebar-right").animate( {width:'290px'}, 400, function(){
        $('#results').append("<label>Search results for '" + searchTerm + "':")
        sendersArray = [];
        countsArray = [];

        for (var i = 0; i < counts.length; i++) {
            var countText = counts[i].sender + ": " + counts[i].msgCount;
            $('#results').append("<div class='lead'>" + countText + "</div>");
            sendersArray.push(counts[i].sender);
            countsArray.push(counts[i].msgCount);
        }
        addChart(sendersArray, countsArray);
    });     
}

function formatMsgElement(msg, previousMsg, searchTerm, searchTermContainsEmoji) {
    var msgElement = $("<div class='msg-container'><div class='msg'></div></div>");

    if (!searchTermContainsEmoji) {
        var regex = new RegExp("(" + preg_quote(searchTerm) + ")", 'gi');
        var messageHtml = msg.message.replace(regex, "<b class='highlighted'><i>$1</i></b>");
    } else {
        var messageHtml = msg.message;
    }
    
    var sendTime = moment(msg.sendDate.timestamp * 1000).format('h:mm A');
    msgElement.find('.msg').html(messageHtml + "<span class='message-time'>" + sendTime + "</span>");

    // if it's the first message or the send date is different
    // to that of the previous message, display the date before the message
    var sendDate = moment(msg.sendDate.timestamp * 1000).format('Do MMMM YYYY');
    if (previousMsg === null) {
        $('#chatPreview').append("<div class='message-date'>" + sendDate + "</div>");
    } else {
        var prevSendDate = moment(previousMsg.sendDate.timestamp * 1000).format('Do MMMM YYYY');
        if (sendDate !== prevSendDate) {
            $('#chatPreview').append("<div class='message-date'>" + sendDate + "</div>");
            msgElement.find('.msg').addClass('first');
        }
    }

    if (msg.sender === senderToShowAsMe) {
        msgElement.find('.msg').addClass('msg-out');
    } else {
        msgElement.find('.msg').addClass('msg-in');
    }

    if (previousMsg === null || msg.sender !== previousMsg.sender || sendDate !== prevSendDate) {
        msgElement.find('.msg').addClass('first');

        if (chat.isGroupChat() && msg.sender !== senderToShowAsMe) {
            msgElement.find('.msg').prepend("<span class='message-sender'>" + msg.sender + "</span>");
        }
    }

    return msgElement;
}

function preg_quote( str ) {
    return (str+'').replace(/([\\\.\+\*\?\[\^\]\$\(\)\{\}\=\!\<\>\|\:])/g, "\\$1");
}

function addChart(labels, data) {
    $('#results').append("<canvas id='chart-area' width='100' height='100'></canvas>");

    var config = {
        type: 'pie',
        data: {
            datasets: [{
                data: data,
                backgroundColor: [
                    '#36a2eb', // light blue
                    '#ffcd56', // light orange
                    '#ff9cb1', // light red
                    '#4bc0c0', // turquoise
                    '#ff9f40', // orange
                ],
                label: 'Search results'
            }],
            labels: labels
        },
        options: {
            responsive: true
        }
    };

    var ctx = document.getElementById("chart-area").getContext("2d");
    window.resultsChart = new Chart(ctx, config);
}
