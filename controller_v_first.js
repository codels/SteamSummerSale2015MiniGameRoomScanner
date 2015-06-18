applicationKPP.controller('controllerVFirst', function ($http, $timeout, $interval) {
        var vm = this;

        vm.version = "0.1";
        vm.minRoomId = 45190;
        vm.maxRoomId = 45210;
        vm.currentRoomId = 0;
        vm.rooms = [];
        vm.accountsSearch = [10098050, 133090071];
        vm.currentMaxRoomId = 0;
        vm.is_start = false;
        vm.search_only_not_started = false;
        vm.minIsBlocked = true;
        vm.maxIsBlocked = true;

        vm.start = function () {
            vm.is_start = true;
            vm.currentRoomId = vm.minRoomId;
            vm.scanRoomsStatus();
        };

        vm.startSearch = function () {
            vm.is_start = true;
            vm.currentRoomId = vm.minRoomId;
            vm.searchNewRoom();
        };

        vm.stop = function () {
            vm.is_start = false;
        };

        vm.scanRoomsStatus = function () {
            if (!vm.is_start) {
                return;
            }
            $http.post('./room_status.php', {room_id: vm.currentRoomId}).then(function (response) {
                if (!vm.minIsBlocked) {
                    if (response.data.room_id >= vm.minRoomId) {
                        if (response.data.status == 3) { //end
                            vm.minRoomId = response.data.room_id + 1;
                        }
                        if (vm.search_only_not_started) {
                            if (response.data.status == 2) { // process
                                vm.minRoomId = response.data.room_id + 1;
                            }
                        }
                    }
                }

                if (response.data.status >= 1 && response.data.status <= 2) {
                    vm.scanAccountInRoom(parseInt(response.data.room_id, 10));
                }

                if (response.data.status == -1) {
                    if (!vm.maxIsBlocked && response.data.room_id > vm.maxRoomId) {
                        vm.maxRoomId = parseInt(response.data.room_id, 10);
                    }
                } else {
                    vm.currentRoomId = vm.currentRoomId + 1;
                    if (!vm.maxIsBlocked && response.data.room_id == vm.maxRoomId) {
                        vm.maxRoomId = response.data.room_id + 1;
                    }
                }

                if (response.data.room_id >= vm.maxRoomId) {
                    vm.currentRoomId = parseInt(vm.minRoomId, 10);
                }

                if (!vm.maxIsBlocked) {
                    if (vm.minRoomId > vm.maxRoomId) {
                        vm.maxRoomId = parseInt(vm.minRoomId);
                    }
                }

                vm.scanRoomsStatus();
            })
        };

        vm.scanAccountInRoom = function (roomId) {
            $http.post('./account_in_room.php', {
                room_id: roomId,
                account_id: vm.accountsSearch
            }).then(function (response) {
                if (_.indexOf(response.data.room_id, vm.rooms) == -1) {
                    if (response.data.exists) {
                        vm.rooms.push(response.data.room_id);
                    }
                } else {
                    if (!response.data.exists) {
                        vm.rooms = _.without(vm.rooms, response.data.room_id);
                    }
                }
            })
        };
    }
);