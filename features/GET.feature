@GET
Feature: Test requesting list of created ToDo items and single ToDo item via GET

  Scenario: Call a not found route
    When I add "Content-Type" header equal to "application/json"
    And I send a "GET" request to "/api/todos/not-found-route"

    Then the response status code should be 404
    And the response should be empty

  Scenario: List of ToDos is empty
    When I add "Content-Type" header equal to "application/json"
    And I send a "GET" request to "/api/todos"

    Then the response should be in JSON
    And the response status code should be 200
    And the JSON node "root" should have 0 elements

  Scenario Outline: Insert some ToDo items
    When I add "Content-Type" header equal to "application/json"
    And I send a "POST" request to "/api/todos" with body:
        """
        {
            "name": "<name>",
            "description": "<description>",
            "tasks": [
              {
                 "name": "<taskName>"
              }
            ]
        }
        """
    Then the response status code should be 201

    Examples:
      | name      | description  | taskName |
      | a1        | desc1        | task1    |
      | a2        | desc2        | task2    |

  Scenario: Request list of all ToDos
    When I add "Content-Type" header equal to "application/json"
    And I send a "GET" request to "/api/todos"

    Then the response should be in JSON
    And the response status code should be 200
    And the JSON node "root" should have 2 elements

    And the JSON node "[0].id" should be equal to the number 1
    And the JSON node "[0].name" should be equal to the string "a1"
    And the JSON node "[0].description" should be equal to the string "desc1"
    And the JSON node "[0].tasks" should have 1 elements
    And the JSON node "[0].tasks[0].name" should be equal to the string "task1"
    And the JSON node "[0].tasks[0].description" should be null

    And the JSON node "[1].id" should be equal to the number 2
    And the JSON node "[1].name" should be equal to the string "a2"
    And the JSON node "[1].description" should be equal to the string "desc2"
    And the JSON node "[1].tasks" should have 1 elements
    And the JSON node "[1].tasks[0].name" should be equal to the string "task2"
    And the JSON node "[1].tasks[0].description" should be null
    And the JSON node "[1].createdAt" should be a datetime
    And the JSON node "[1].updatedAt" should be a datetime

  Scenario: Request a single existing ToDo
    When I add "Content-Type" header equal to "application/json"
    And I send a "GET" request to "/api/todos/1"

    Then the response should be in JSON
    And the response status code should be 200
    And the JSON node "id" should be equal to the number 1
    And the JSON node "name" should be equal to the string "a1"
    And the JSON node "description" should be equal to the string "desc1"
    And the JSON node "tasks" should have 1 elements
    And the JSON node "tasks[0].name" should be equal to the string "task1"
    And the JSON node "tasks[0].description" should be null

  Scenario: Request a single not existing ToDo
    When I add "Content-Type" header equal to "application/json"
    And I send a "GET" request to "/api/todos/1000"

    Then the response status code should be 404
    And the response should be empty