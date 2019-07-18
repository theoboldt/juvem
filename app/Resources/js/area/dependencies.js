$(function () {
    var el = $('#vis-network');
    if (!el.length) {
        return;
    }
    el.on('resize', function () {
        el.css('height', window.innerHeight - 75 + 'px');
    }());

    var filterGroups = null,
        showParticipants = null,
        filterIncludeChoices = [],
        container = document.getElementById('vis-network'),
        nodesSet = new vis.DataSet(el.data('nodes')),
        edgesSet = new vis.DataSet(el.data('edges')),
        dataView = new vis.DataView(nodesSet, {
            filter: function (item) {
                if (filterGroups === null) {
                    return true;
                }
                var type = item.type;

                if (!showParticipants && type === 'participant') {
                    return false;
                }
                if (type === 'choice' && $.inArray(item.bid, filterIncludeChoices) === -1) {
                    return false;
                }

                var acceptGroup = false;
                $.each(filterGroups, function (groupName, acceptedValues) {
                    var groupValue = item[groupName];
                    acceptGroup = false;

                    $.each(acceptedValues, function (key, acceptedConfiguration) {
                        var acceptedValue = acceptedConfiguration.value,
                            filterableNodes = acceptedConfiguration.nodes;

                        if ($.inArray(type, filterableNodes) === -1
                            || groupValue === acceptedValue) {
                            acceptGroup = true;
                            return false;
                        }
                    });
                    if (!acceptGroup) {
                        return false;
                    }
                });

                return acceptGroup;
            }
        });
    var data = {
        nodes: dataView,
        edges: edgesSet
    };

    var connectToChoice = function (nodeHuman, nodeChoice) {
        var nodeHumanId = nodeHuman.id,
            nodeChoiceId = nodeChoice.id,
            newBid = nodeChoice.bid,
            connectedNodes = network.getConnectedNodes(nodeHumanId);

        if ($.inArray(nodeChoiceId, connectedNodes) !== -1) {
            return false; //already part of this group
        }

        //remove other connections to same bid
        var connectedEdges = network.getConnectedEdges(nodeHumanId);
        $.each(connectedEdges, function (key, connectedEdgeId) {
            var connectedEdge = edgesSet.get(connectedEdgeId);
            if (connectedEdge.bid === newBid) {
                edgesSet.remove(connectedEdgeId);
            }
        });

        var newEdges = edgesSet.add({
            from: nodeHumanId,
            to: nodeChoiceId,
            bid: newBid,
            choiceId: nodeChoice.choiceId,
            color: {color: nodeChoice.color},
            dashes: true
        });
        dataView.refresh();
        $.ajax({
            type: 'POST',
            url: '/admin/event/' + el.data('eid') + '/dependencies/change_group_assignment/' + nodeHumanId,
            data: {
                _token: el.data('token'),
                bid: newBid,
                choiceId: nodeChoice.choiceId
            },
            success: function (response) {
                if (response.success) {
                    console.log(newEdges[0]);
                    edgesSet.update({id: newEdges[0], dashes: false});
                } else {
                    location.reload();
                }
            },
            error: function () {
                location.reload();
            }
        });
        network.addEdgeMode();
    };

    var options = {
        layout: {improvedLayout: false},
        manipulation: {
            enabled: false,
            addEdge: function (edgeData, callback) {
                if (edgeData.from === edgeData.to) {
                    return; //not adding self connections
                } else {
                    var nodeFrom = nodesSet.get(edgeData.from),
                        nodeFromType = nodeFrom.type,
                        nodeTo = nodesSet.get(edgeData.to),
                        nodeToType = nodeTo.type;

                    if ((nodeFromType !== 'choice' && nodeToType !== 'choice')
                        || (nodeFromType === 'choice' && nodeToType === 'choice')
                    ) {
                        return; //only able to connect choices but not only choices
                    }
                    if (nodeFromType === 'choice') {
                        //swap nodes in order to ensure connection has correct direction
                        var nodeTmp = nodeFrom;
                        nodeFrom = nodeTo;
                        nodeTo = nodeTmp;
                    }

                    connectToChoice(nodeFrom, nodeTo, callback);
                }
            }
        }
    };
    var network = new vis.Network(container, data, options);

    network.on("doubleClick", function (params) {
        if (params.nodes.length !== 1) {
            return;
        }
        var nodeId = params.nodes[0];
        if (network.isCluster(nodeId)) {
            network.openCluster(nodeId);
        } else {
            var node = nodesSet.get(nodeId),
                clusterOptionsByData;

            if (node.type !== 'choice') {
                return;
            }

            clusterOptionsByData = {
                processProperties: function (clusterOptions, childNodes) {
                    clusterOptions.label = node.label + ' [' + childNodes.length + ']';
                    clusterOptions.color = node.color;
                    clusterOptions.shape = node.shape;
                    return clusterOptions;
                },
                clusterNodeProperties: {
                    borderWidth: 2,
                    shapeProperties: {borderDashes: [5, 2]}
                }
            };
            network.clusterByConnection(nodeId, clusterOptionsByData);
        }
    });
    network.on("selectNode", function (params) {
        if (params.nodes.length !== 1) {
            return;
        }
        var nodeId = params.nodes[0],
            node = nodesSet.get(nodeId);
    });
    $('.modes label').on('change', function () {
        var el = $(this),
            elInput = el.find('input'),
            mode = elInput.attr('id');

        if (mode === 'add-edge') {
            network.addEdgeMode();
        } else {
            network.disableEditMode();
        }
    });

    var updateFilterConfiguration = function () {
            filterGroups = {};
            filtersInputEls.each(function () {
                var filterEl = $(this);
                if (filterEl.prop('checked')) {
                    var property = filterEl.data('property'),
                        value = filterEl.data('value'),
                        nodes = filterEl.data('nodes');
                    if (!filterGroups[property]) {
                        filterGroups[property] = [];
                    }
                    filterGroups[property].push({
                        value: value,
                        nodes: nodes.split(',')
                    });
                }
            });
            showParticipants = false;
            filterIncludeChoices = [];
            filterIncludeChoicesEls.each(function () {
                var filterEl = $(this);
                if (filterEl.prop('checked')) {
                    var type = filterEl.data('type'),
                        bid = filterEl.data('bid');

                    switch (type) {
                        case 'participant':
                            showParticipants = true;
                            break;
                        case 'choice':
                            filterIncludeChoices.push(bid);
                            break;
                    }
                }
            });
            dataView.refresh();
        },
        filtersInputEls = $('.filters input'),
        filterIncludeChoicesEls = $('.filter-nodes input');
    filtersInputEls.on('change', updateFilterConfiguration);
    filterIncludeChoicesEls.on('change', updateFilterConfiguration);
});