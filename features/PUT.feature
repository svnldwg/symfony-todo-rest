@PUT
Feature: Test updating a ToDo item via PUT

  Scenario: Try to update not existing ToDo
    When I add "Content-Type" header equal to "application/json"
    And I send a "PUT" request to "/api/todos/1000" with body:
    """
    {
        "name": "Updated name",
        "description": "Updated description"
    }
    """

    Then the response status code should be 404
    And the response should be empty

  Scenario: Create a new ToDo
    When I add "Content-Type" header equal to "application/json"
    And I send a "POST" request to "/api/todos" with body:
        """
        {
            "name": "some name",
            "description": "some description",
            "tasks": [
                {"name": "first task"},
                {"name": "second task"}
            ]
        }
        """

    Then the response status code should be 201
    And the header "Location" should be equal to "http://localhost/api/todos/1"

  Scenario: Try to update ToDo with missing "name" field
    When I add "Content-Type" header equal to "application/json"
    And I send a "PUT" request to "/api/todos/1" with body:
    """
    {
        "description": "Updated description"
    }
    """

    Then the response status code should be 400
    And the response should be in JSON
    And the JSON should be equal to:
    """
    {
         "errors": [
              "name: This value should not be blank."
         ]
    }
    """

  Scenario: Successfully update ToDo
    When I add "Content-Type" header equal to "application/json"
    And I send a "PUT" request to "/api/todos/1" with body:
    """
    {
        "name": "Updated name",
        "description": "Updated description",
        "tasks": [
            {"name": "Updated first task"},
            {"name": "Updated second task", "description": "I have a description now"},
            {"name": "Newly added third task"}
        ]
    }
    """

    Then the response status code should be 200
    And the response should be in JSON

    And the JSON node "id" should be equal to the number 1
    And the JSON node "name" should be equal to the string "Updated name"
    And the JSON node "description" should be equal to the string "Updated description"
    And the JSON node "tasks" should have 3 elements
    And the JSON node "tasks[0].name" should be equal to the string "Updated first task"
    And the JSON node "tasks[0].description" should be null
    And the JSON node "tasks[0].id" should not exist
    And the JSON node "tasks[0].todo" should not exist
    And the JSON node "tasks[1].name" should be equal to the string "Updated second task"
    And the JSON node "tasks[1].description" should be equal to the string "I have a description now"
    And the JSON node "tasks[2].name" should be equal to the string "Newly added third task"
    And the JSON node "tasks[2].description" should be null
    And the JSON node "createdAt" should be a datetime
    And the JSON node "updatedAt" should be a datetime
