Utils.ajax.classes.blog = {
	vote: function(id, way, object) {
        Utils.ajax.query({
			url: '/ajax.php',
			data: {
                class: "Blog",
                method: "vote",
                id: id,
                way: way
            },
			method: 'POST',
			timeout: 5000,
			success: function(response) {
				if (response.success) {
					object.parentNode.getElementsByClassName('number')[0].innerHTML = response.rating;
				} else {
					alert(response.message);
				};
			},
			error: function(type) {
				switch (type) {
					case 'timeout':
						alert('The time for request has ended. Try later.');
						break;
						
					case 'error':
						alert('An error while the request sent. Try later.');
						break;
				};
			}
		});
	}
};