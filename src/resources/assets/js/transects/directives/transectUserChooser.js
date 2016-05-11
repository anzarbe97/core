/**
 * @namespace dias.ui.users
 * @ngdoc directive
 * @name transectUserChooser
 * @memberOf dias.transects
 * @description An input field to find a user who has annotations in a transect
 * @example
// HTML
<input placeholder="Search by user name" data-transect-user-chooser="addUser" data-transect-id="1" />

// Controller
$scope.addUser = function (user) {
    // do something
};
 */
angular.module('dias.transects').directive('transectUserChooser', function () {
      "use strict";

      return {
         restrict: 'A',

         scope: {
            select: '=transectUserChooser',
            id: '=transectId'
         },

         replace: true,

         template: '<input type="text" data-ng-model="selected" data-uib-typeahead="name(user) for user in find($viewValue)" data-typeahead-wait-ms="250" data-typeahead-on-select="select($item)"/>',

         controller: function ($scope, TransectUsers) {
            $scope.name = function (user) {
                if (user && user.firstname && user.lastname) {
                    return user.firstname + ' ' + user.lastname;
                }

                return '';
            };

            $scope.find = function (query) {
               return TransectUsers.find({
                    transect_id: $scope.id,
                    query: encodeURIComponent(query)
                }).$promise;
            };
         }
      };
   }
);
