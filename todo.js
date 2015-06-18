angular.module('todoApp', [])
    .controller('TodoListController', function ($http, $timeout, $interval) {
        var vm = this;

        vm.minRoomId = 44347;
        vm.maxRoomId = 45360;
        vm.currentRoomId = 0;
        vm.newRooms = [];
        vm.rooms = [];
        vm.autoRefresh = true;
        vm.maxPlayers = 1400;
        vm.accountsSearch = [10098050, 133090071];
        vm.currentMaxRoomId = 0;
        vm.is_start = false;
        vm.search_only_not_started = false;
        vm.minIsBlocked = false;
        vm.maxIsBlocked = false;
        vm.lastRoomId = 45530;
        vm.threads = 5;
        vm.range = 100;

        vm.start = function () {
            vm.is_start = true;
            vm.currentRoomId = vm.minRoomId;
            vm.scanRoomsStatus();
        };

        vm.startSearch = function () {
            vm.is_start = true;
            vm.currentRoomId = vm.lastRoomId;
            vm.newRooms.push({id: vm.currentRoomId, status: 0, players: 0, activePlayers: 0, refresh: 0});
            vm.startRefreshing();
            vm.searchNewRoom();
        };

        vm.stop = function () {
            vm.is_start = false;
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
                            vm.newRooms[i].activePlayers = response.data.activePlayers;
                            vm.newRooms[i].status = response.data.status;
                            vm.newRooms[i].refresh = 1;
                        }
                    }
                    // vm.startRefreshing(vm.currentRoomId);
                    vm.currentRoomId++;
                    vm.newRooms.push({id: vm.currentRoomId, status: 0});
                    vm.searchNewRoom();
                }
            })
        };

        vm.startRefreshing = function () {
            console.log('refresh');
            $interval(function () {
                for (i = 0; i < vm.newRooms.length; i++) {
                    if (vm.newRooms[i].refresh == 1) {
                        $http.post('./room_status.php', {room_id: vm.newRooms[i].id}).then(function (response) {
                            for (j = 0; j < vm.newRooms.length; j++) { //дерьмокод
                                if (vm.newRooms[j].id == response.data.id) {
                                    vm.newRooms[j].players = response.data.players;
                                    if (response.data.players > vm.maxPlayers) {
                                        vm.newRooms[j].refresh = 0;
                                    }
                                    vm.newRooms[j].activePlayers = response.data.activePlayers;
                                    vm.newRooms[j].status = response.data.status;
                                }
                            }
                        })
                    }
                }
            }, 3000);
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
                        vm.foundedRooms.push(response.data.room_id);
                    }
                } else {
                    if (!response.data.exists) {
                        vm.foundedRooms = _.without(vm.foundedRooms, response.data.room_id);
                    }
                }
            })
        };
    }
);