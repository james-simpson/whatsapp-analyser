var chat = {

	id: null,
	members: [],
	messages: [],
	senderToShowAsMe: null,

	clear: function() {
		chat.members = [];
		chat.messages = [];
		$('#chatPreview').empty();
        $('#results').empty();
	},
	getOverview: function(callback) {
		$.post("chatOverview", { chatId: chat.id }, function(response) {
			response.data.forEach(function(member) {
	            chat.members.push({
	                name: member.sender,
	                msgCount: member.msgCount
	            });
			});

	        callback();
	    });
	},
	isGroupChat: function() {
		return chat.members.length > 2;
	},
}

var chatMembers = new Vue({
  el: '#chatMembers',
  delimiters: ['{', '}'],
  data: {
  	chat: chat
  },
  methods: {
    hasMembers: function() {
    	return chat.members.length > 0;
    }
  }
});

var modal = new Vue({
	el: '#modal',
	delimiters: ['{', '}'],
	data: {
		chat: chat
	},
	methods: {
		chooseChatMember: function() {
			chat.senderToShowAsMe = $('#selChatMember').val();
		}
	}
});

var fileImport = new Vue({
	el: '#frmLoadChat',
	delimiters: ['{', '}'],
	data: {
		chat: chat,
		loading: false,
		message: ""
	},
	methods: {
		chatFileChanged: function() {
			if ($("#importFile").val() !== "") {
	            this.uploadChatFile();
	        }
		},
		uploadChatFile: function() {

		    fileImport.loading = true;
		    fileImport.message = "Uploading file...";
		    chat.clear();

		    var formData = new FormData();    
		    formData.append('chatFile', document.getElementById('importFile').files[0], "test filename" );

		    // upload the chat file
		    new Promise(function(resolve, reject) {
					$.ajax({
		            url: "upload",
		            type: "POST",
		            data: formData,
		            processData: false,
		            contentType: false,
		            success: function(result) {
		            	chat.id = result.chatId;
		            	resolve(result);                
		            }
		        });
			})

		    // parse the messages and save them to the DB
			.then(function(result) {		
		        fileImport.message = "Extracting messages...";
		        $.post("extractMessages", { chatId: chat.id }, function(response) {
		            return result; 
		        });
			})

			// display the chat details, i.e. names of chat members
			// and no. of messages each member has sent
			.then(function(result) {
				fileImport.message = "Getting chat overview...";
				chat.getOverview(function() {
					fileImport.loading = false;
			    	fileImport.message = "Chat loaded.";

			        // ask the user to say which chat member they are so we know 
			        // which messages to display on the right hand side
			        $('#modal').modal();
				});
			});
		}
	}
})

var searchForm = new Vue({
	el: '#frmSearchMessages',
	delimiters: ['{', '}'],
	data: {
		chat: chat,
		loading: false,
		buttonText: "Search"
	},
	methods: {
		search: function() {

			searchForm.loading = true;
			searchForm.buttonText = "Searching messages...";
			$('#btnSearch').blur();
	        $('#results').empty();
	 		searchSummary.slideOut();
	        $('#messages').empty();
	        $('#messages').append("<div class='loadingText lead'>Searching messages...</div>");

	        chat.messages = [];

	        var searchTerm = $('#searchTerm').val();
	        messageList.searchTerm = searchTerm;
	        $.post("searchMessages", { chatId: chat.id, searchTerm: searchTerm }, function(response) {

	            $('#messages').find('.loadingText').remove();

	            var messages = JSON.parse(response.messages);
	            displaySearchCounts(response.counts, searchTerm)

	            if (messages.length === 0) {
	                $('#messages').append("<div class='no-messages lead'>No matching messages</div>");
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

	                // var msgElement = formatMsgElement(msg, previousMsg, searchTerm, searchTermContainsEmoji) ;
	                // $('#messages').append(msgElement);

	                var sendDate = moment(msg.sendDate.timestamp * 1000).format('Do MMMM YYYY');
	                var sendTime = moment(msg.sendDate.timestamp * 1000).format('h:mm A');
	                chat.messages.push(
	                	{
	                		sender: msg.sender,
	                		text: msg.message,
	                		date: sendDate,
	                		time: sendTime
	                	}
	                )
	            };

	            searchForm.buttonText = "Search";
	            searchForm.loading = false;
	        });
		}
	}
});

var searchSummary = new Vue({
	el: '#searchSummary',
	delimiters: ['{', '}'],
	data: {
		chat: chat
	},
	methods: {
		slideOut: function() {
			$(".sidebar-right").animate({width:'0'}, 350);
		}
	}
});

// register
Vue.component('message-item', {
  delimiters: ['{', '}'],
  template: `<div>
  				<div class="message-date" v-show="showDate">{ msg.date }</div>
                <div class="msg-container">
                    <div 
                        class="msg"
                        v-bind:class="{'msg-out': isMessageOut, 'msg-in': !isMessageOut, 'first': firstClass}"
                        v-html="messageTextHtml"
                    >
                        <span class="message-time">{ msg.time }</span>
                    </div>
                </div>
            </div>`,
    props: ['msg', 'previousMsg', 'searchTerm', 'senderToShowAsMe'],
    computed: {
    	showDate: function() {
    		return typeof this.previousMsg === 'undefined' || this.msg.date !== this.previousMsg.date;
    	},
		firstClass: function() {			
			return this.showDate || this.msg.sender !== this.previousMsg.sender;
		},
		isMessageOut: function() {
			// console.log(this.msg)
			return this.msg.sender === this.senderToShowAsMe;
		},
		messageTextHtml: function() {
			var regex = new RegExp("(" + preg_quote(this.searchTerm) + ")", 'gi');
        	return this.msg.text.replace(regex, "<span class='highlighted'>$1</span>");
		}
    }
})

var messageList = new Vue({
	el: '#messages',
	delimiters: ['{', '}'],
	data: {
		chat: chat,
		searchTerm: ""
	}
});