import React, { useEffect, useState } from 'react';
import SearchModal from './SearchModal';

const GlobalSearch = ({ displayInput = false }) => {
    const [isModalOpen, setIsModalOpen] = useState(false);
    const [searchQuery, setSearchQuery] = useState('');

    useEffect(() => {
        const url = new URL(window.location.href);
        const queryParam = url.searchParams.get('q');
        if (queryParam) {
            setSearchQuery(queryParam);
        }
    }, []);

    const handleButtonClick = (e) => {
        e.preventDefault();
        setIsModalOpen(true);
    };

    useEffect(() => {
        const form = document.getElementById('global-search-form');
        if (form) {
            form.hidden = true;
        }
    }, []);

    return (
        <>
            {isModalOpen && <SearchModal
                isOpen={isModalOpen}
                onClose={() => setIsModalOpen(false)}
            />}
            {displayInput ? <div class="input-group mb-3 mt-sm-3" onClick={handleButtonClick}>
                <input autocomplete="off" class="form-control shadow-none" id="globalsearchinput" name="q" placeholder="TYPO3 documentation..." type="text" value={searchQuery}></input>
                <button class="btn btn-light"><i class="fa fa-search"></i>&nbsp;<span class="d-none d-md-inline">Search</span></button>
            </div> :
                <button onClick={handleButtonClick} class="btn btn-light"><i class="fa fa-search"></i>&nbsp;<span class="d-none d-md-inline">Search</span></button>}
        </>
    );
};

export default GlobalSearch;
