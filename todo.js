angular.module('todoApp', [])
    .controller('TodoListController', function ($http) {
        var todoList = this;

        todoList.minRoomId = 45360;
        todoList.maxRoomId = 45360;
        todoList.currentRoomId = 0;
        todoList.rooms = [];
        todoList.accountsSearch = [10098050, 133090071];
        todoList.currentMaxRoomId = 0;
        todoList.is_start = false;
        todoList.search_only_not_started = false;
        todoList.minIsBlocked = false;
        todoList.maxIsBlocked = false;

        todoList.start = function () {
            todoList.is_start = true;
            todoList.currentRoomId = todoList.minRoomId;
            todoList.scanRoomsStatus();
        };

        todoList.stop = function() {
            todoList.is_start = false;
        };

        todoList.scanRoomsStatus = function () {
            if (!todoList.is_start) {
                return;
            }
            $http.post('./room_status.php', {room_id: todoList.currentRoomId}).then(function (response) {
                if (!todoList.minIsBlocked) {
                    if (response.data.room_id >= todoList.minRoomId) {
                        if (response.data.status == 3) { //end
                            todoList.minRoomId = response.data.room_id + 1;
                        }
                        if (todoList.search_only_not_started) {
                            if (response.data.status == 2) { // process
                                todoList.minRoomId = response.data.room_id + 1;
                            }
                        }
                    }
                }

                if (response.data.status >= 1 && response.data.status <= 2) {
                    todoList.scanAccountInRoom(parseInt(response.data.room_id, 10));
                }

                if (response.data.status == -1) {
                    if (!todoList.maxIsBlocked && response.data.room_id > todoList.maxRoomId) {
                        todoList.maxRoomId = parseInt(response.data.room_id, 10);
                    }
                } else {
                    todoList.currentRoomId = todoList.currentRoomId + 1;
                    if (!todoList.maxIsBlocked && response.data.room_id == todoList.maxRoomId) {
                        todoList.maxRoomId = response.data.room_id + 1;
                    }
                }

                if (response.data.room_id >= todoList.maxRoomId) {
                    todoList.currentRoomId = parseInt(todoList.minRoomId, 10);
                }

                if (!todoList.maxIsBlocked) {
                    if (todoList.minRoomId > todoList.maxRoomId) {
                        todoList.maxRoomId = parseInt(todoList.minRoomId);
                    }
                }

                todoList.scanRoomsStatus();
            })
        };

        todoList.scanAccountInRoom = function (roomId) {
            $http.post('./account_in_room.php', {room_id: roomId, account_id: todoList.accountsSearch}).then(function (response) {
                if (_.indexOf(response.data.room_id, todoList.rooms) == -1) {
                    if (response.data.exists) {
                        todoList.rooms.push(response.data.room_id);
                    }
                } else {
                    if (!response.data.exists) {
                        todoList.rooms = _.without(todoList.rooms, response.data.room_id);
                    }
                }
            })
        };
    });