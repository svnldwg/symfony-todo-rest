@delete
Feature: Test deleting a ToDo item
  Scenario: Create a new ToDo
    When I add "Content-Type" header equal to "application/json"
    And I send a "POST" request to "/api/todos" with body:
        """
        {
            "name": "Original name",
            "description": "Original description",
            "tasks": [
                {"name": "first task"},
                {"name": "second task"}
            ]
        }
        """

    Then the response status code should be 201
    And the header "Location" should be equal to "http://localhost/api/todos/1"

  Scenario: Successfully delete ToDo
    When I send a "DELETE" request to "/api/todos/1"

    Then the response status code should be 204
    And the response should be empty

  Scenario: Try to delete not existing ToDo
    When I send a "DELETE" request to "/api/todos/1"

    Then the response status code should be 404
    And the response should be empty
