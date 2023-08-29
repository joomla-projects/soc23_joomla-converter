(function (document, Joomla) {
    "use strict";
  
    /**
     * @copyright  (C) 2018 Open Source Matters, Inc. <https://www.joomla.org>
     * @license    GNU General Public License version 2 or later; see LICENSE.txt
     */
    if (!Joomla) {
      throw new Error("Joomla API is not properly initialised");
    }
    var data = migratetojoomla.progress.importstring;
    function addsteps() {
      alert("There is an error");
      console.log("I am here kaushik");
      var element = document.getElementById("migratetojoomla_listgroup");
      for (const value of data) {
        var step = document.createElement("li");
        step.className = "list-group-item bg-primary text-white";
        step.innerHTML = element[1];
        element.appendChild(step);
      }
    }
  
    console.log("I am here kaushik");
    document.addEventListener("DOMContentLoaded", function () {
      alert("There is an error");
      document
        .getElementById("migratetojoomla_startmigrate")
        .addEventListener("click", addsteps);
    });
  })();
  