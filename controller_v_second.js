applicationKPP.controller('controllerVSecond', function ($http, $timeout, $interval) {
        var vm = this;

        vm.version = "0.2 —  Bizarre";
        vm.currentRoomId = 0;
        vm.newRooms = [];
        vm.autoRefresh = true;
        vm.maxPlayers = 1400;
        vm.accountsSearch = [10098050, 133090071];
        vm.is_start = false;
        vm.lastRoomId = 45620;
        vm.refreshTimeout = 10000;
        vm.foundedRooms = [];
        vm.stalkRoom = 0;

        vm.start = function () {
            vm.is_start = true;
            vm.currentRoomId = vm.minRoomId;
            vm.scanRoomsStatus();
        };

        vm.startSearch = function () {
            vm.newRooms = [];
            vm.is_start = true;
            vm.currentRoomId = vm.lastRoomId;
            vm.newRooms.push({id: vm.currentRoomId, status: 0, players: 0, refresh: 0});
            vm.searchNewRoom();
        };

        vm.stop = function () {
            vm.is_start = false;
        };

        vm.stalk = function () {
            $http.post('./account_in_room.php', {
                room_id: vm.stalkRoom,
                account_id: vm.accountsSearch
            }).then(function (response) {
                if (!response.data.exists) {
                    var alert = new Audio('alert.mp3');
                    alert.play();
                }
                $timeout(function () {
                        vm.stalk();
                }, 5000);
            })
        };

        vm.searchNewRoom = function () {
            if (!vm.is_start) {
                return;
            }
            $http.post('./room_status.php', {room_id: vm.currentRoomId}).then(function (response) {
                if (!response || response.data.status == -1) {
                    $timeout(function () {
                        vm.searchNewRoom();
                    }, 3000);
                }
                else {
                    for (i = 0; i < vm.newRooms.length; i++) {
                        if (vm.newRooms[i].id == vm.currentRoomId) {
                            vm.newRooms[i].players = response.data.players;
                            vm.newRooms[i].status = response.data.status;
                            vm.newRooms[i].refresh = 1;
                        }
                    }
                    if (vm.autoRefresh) {
                        vm.startRefreshing(vm.currentRoomId);
                    }
                    vm.lastRoomId = vm.currentRoomId;
                    vm.currentRoomId++;
                    vm.newRooms.push({id: vm.currentRoomId, status: 0});
                    vm.searchNewRoom();
                }
            })
        };

        vm.startRefreshing = function (roomId) {
            console.log('refreshing' + roomId);
            $http.post('./account_in_room.php', {
                room_id: roomId,
                account_id: vm.accountsSearch
            }).then(function (response) {
                if (_.indexOf(response.data.room_id, vm.foundedRooms) == -1) {
                    if (response.data.exists) {
                        vm.foundedRooms.push(response.data.room_id);
                    }
                } else {
                    if (!response.data.exists) {
                        vm.foundedRooms = _.without(vm.foundedRooms, response.data.room_id);
                    }
                }
                for (j = 0; j < vm.newRooms.length; j++) { //дерьмокод
                    if (vm.newRooms[j].id == roomId) {
                        vm.newRooms[j].players = response.data.players;
                        if (response.data.players > vm.maxPlayers) {
                            vm.newRooms[j].refresh = 0;
                        }
                        else {
                            $timeout(function () {
                                if (vm.autoRefresh && vm.is_start) {
                                    vm.startRefreshing(roomId);
                                }
                            }, vm.refreshTimeout);
                        }
                    }
                }
            })
        };
    }
);