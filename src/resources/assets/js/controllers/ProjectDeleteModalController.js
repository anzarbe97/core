/**
 * @namespace dias.projects
 * @ngdoc controller
 * @name ProjectDeleteModalController
 * @memberOf dias.projects
 * @description Handles the confirmation of deletion of a project.
 * @example

 */
angular.module('dias.projects').controller('ProjectDeleteModalController', function ($scope, Project) {
		"use strict";

		$scope.force = false;

		var deleteSuccess = function (response, status) {
			$scope.$close('success');
		};

		var deleteError = function(response) {
			if (response.status === 400) {
				$scope.force = true;
			} else {
				$scope.$close('error');
			}
		};

		$scope.delete = function (id) {
			var data = $scope.force ? {id: id, force: true} : {id: id};
			Project.delete(data, deleteSuccess, deleteError);
		};
	}
);
