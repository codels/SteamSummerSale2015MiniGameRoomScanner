applicationKPP.controller('controllerVFirst', function ($http, $timeout, ServiceSoundAlarm) {
        var vm = this;

        vm.version = "0.2";


        vm.accountsSearch = [10098050, 133090071];
        vm.is_start = false;
        vm.debug = true;

        vm.additionalRoomScan = [0];
        vm.roomsScanning = [];
        vm.startRoomId = 49319;
        vm.roomLastId = 0;
        vm.ignoreStartedRoom = false;
        vm.ignoreCountPlayers = 900;

        vm.start = function () {
            vm.is_start = true;
            vm.roomLastId = vm.startRoomId;
            _.each(vm.additionalRoomScan, function (roomId) {
                if (roomId) {
                    vm.addScanRoom(roomId);
                }
            });
            vm.scanLastId();
        };

        vm.stop = function () {
            vm.is_start = false;
        };

        vm.alert = function (msg) {
            if (vm.debug) {
                console.log('ALERT!!! ' + msg);
            } else {
                ServiceSoundAlarm.playSound();
            }
        };

        vm.addScanRoom = function (id, players, founded, disabled, accounts_founded) {
            var room = {
                id: id,
                players: players || -1,
                founded: founded || false,
                disabled: disabled || false,
                accounts_found: accounts_founded || []
            };
            vm.roomsScanning.push(room);
            vm.scanRoom(room);
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
                            vm.addScanRoom(
                                response.data.room_id,
                                response.data.players,
                                false,
                                false,
                                []
                            );
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
            $http.post('./search_accounts_in_room_json.php', {
                room_id: room.id,
                account_id: vm.accountsSearch
            }).then(function (response) {
                var alertAlready = false;

                if (_.has(response, 'data') && _.has(response.data, 'room_id')) {
                    if (_.has(response.data, 'exists')) {
                        if (!alertAlready && room.founded != response.data.exists) {
                            alertAlready = true;
                            vm.alert('change founded');
                        }
                        room.founded = response.data.exists;
                    }

                    if (_.has(response.data, 'players')) {
                        room.players = response.data.players;
                    }

                    if (_.has(response.data, 'accounts_found')) {
                        var accountsFounded = _.values(response.data.accounts_found);
                        if (!alertAlready && (_.difference(room.accounts_found, accountsFounded).length > 0
                            || _.difference(accountsFounded, room.accounts_found).length > 0)) {
                            //alertAlready = true;
                            vm.alert('change accounts founded');
                        }
                        room.accounts_found = accountsFounded;
                    }

                    if (room.players <= vm.ignoreCountPlayers || room.founded) {
                        $timeout(function () {
                            vm.scanRoom(room);
                        }, 1000);
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