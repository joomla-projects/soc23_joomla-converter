  /**
   * @copyright  (C) 2018 Open Source Matters, Inc. <https://www.joomla.org>
   * @license    GNU General Public License version 2 or later; see LICENSE.txt
   */

  "use strict";

  var data = Joomla.getOptions('com_migratetojoomla.importstring');

  function addsteps() {

    var element = document.getElementById("migratetojoomla_listgroup");
    for (const value of data) {
      var step = document.createElement("li");
      step.className = "list-group-item bg-info text-dark";
      step.innerHTML = value;
      element.appendChild(step);
    }
  }


  document.addEventListener("DOMContentLoaded", function () {
    document
      .getElementById("migratetojoomla_startmigrate")
      .addEventListener("click", addsteps);
  });
