applicationKPP.controller('controllerVFirst', function ($http, $timeout, $interval) {
        var vm = this;

        vm.version = "0.2";


        vm.accountsSearch = [10098050, 133090071];
        vm.is_start = false;

        vm.roomsScanning = [];
        vm.startRoomId = 45630;
        vm.roomLastId = 0;
        vm.ignoreStartedRoom = false;
        vm.ignoreCountPlayers = 900;


        vm.start = function () {
            vm.is_start = true;
            vm.roomLastId = vm.startRoomId;
            vm.scanLastId();
        };

        vm.stop = function () {
            vm.is_start = false;
        };

        vm.scanLastId = function () {
            if (!vm.is_start) {
                return;
            }

            $http.post('./room_info_json.php', {room_id: vm.roomLastId}).then(function (response) {
                var timeoutTime = 0;
                if (_.has(response.data, 'room_id') && _.has(response.data, 'status')) {
                    if (response.data.status == -1) {
                        //
                        timeoutTime = 1000;
                    } else if (response.data.status == 3) {
                        ++vm.roomLastId;
                        //nothing
                    } else {
                        ++vm.roomLastId;
                        if (response.data.status != 2 || !vm.ignoreStartedRoom) {
                            var room = {
                                id: response.data.room_id,
                                players: response.data.players,
                                founded: false,
                                disabled: false,
                                accounts_found: []
                            };
                            vm.roomsScanning.push(room);
                            vm.scanRoom(room);
                        }
                    }
                }
                $timeout(vm.scanLastId, timeoutTime);
            });
        };

        vm.scanRoom = function (room) {
            if (!vm.is_start) {
                return;
            }
            $http.post('./search_accounts_in_room.php', {
                room_id: room.id,
                account_id: vm.accountsSearch
            }).then(function (response) {
                if (_.has(response, 'data') && _.has(response.data, 'room_id')) {
                    if (_.has(response.data, 'exists')) {
                        room.founded = response.data.exists;
                    }

                    if (_.has(response.data, 'players')) {
                        room.players = response.data.players;
                    }

                    if (room.players <= vm.ignoreCountPlayers || room.founded) {
                        $timeout(function() { vm.scanRoom(room); }, 1000);
                    } else {
                        room.disabled = true;
                    }
                } else {
                    // retry instant scan
                    vm.scanRoom(room);
                }
            })
        };

    }
);