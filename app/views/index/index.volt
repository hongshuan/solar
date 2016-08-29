{% extends "layouts/base.volt" %}

{% block main %}
  <div class="w3-container">
    <div id="content">
      <div class="demo-container">
        <div id="placeholder" class="demo-placeholder"></div>
      </div>
    </div>
  </div>
{% endblock %}

{% block jscode %}
{% endblock %}

{% block domready %}
  ajaxCall('/ajax/data', { stn: 2, dev: 'mb-080', col: 'volts_a' },
    function(data) {
      var options = {
        series: { lines: { show: true }, shadowSize: 0 },
        xaxis: { mode: 'time',
                 panRange: [data[0][0], data[data.length-1][0]],
                 zoomRange: [data[0][0], data[data.length-1][0]] 
               },
        yaxis: { panRange: [0, 500] },
        zoom: { interactive: false },
        pan: { interactive: true }
      };
      $.plot("#placeholder", [ data ], options);
    },
    function(message) {
      //showError(message);
    }
  );
{% endblock %}
