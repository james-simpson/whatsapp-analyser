var chat = {
	id: null,
	members: [],
	messages: [],
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

var selChatMember = new Vue({
  el: '#selChatMember',
  delimiters: ['{', '}'],
  data: {
  	chat: chat
  },
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
    uploadChatFile: function() {

    	var self = this;

        this.loading = true;
        this.message = "Uploading file...";
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
            self.message = "Extracting messages...";
            $.post("extractMessages", { chatId: chat.id }, function(response) {
                return result; 
            });
		})

		// display the chat details, i.e. names of chat members
		// and no. of messages each member has sent
		.then(function(result) {
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